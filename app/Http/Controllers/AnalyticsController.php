<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Complaint;
use App\Models\LeaveRequest;
use App\Models\Resident;
use App\Models\ResidentStay;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Analytics/Index', [
            'buildings' => fn() => Building::orderBy('name')->get(['id', 'name']),
            'filterOptions' => fn() => [
                'courses' => Resident::whereNotNull('course')->distinct()->orderBy('course')->pluck('course'),
                'institutes' => Resident::whereNotNull('institute')->distinct()->orderBy('institute')->pluck('institute'),
                'batches' => Resident::whereNotNull('batch')->distinct()->orderBy('batch')->pluck('batch'),
                'years' => Resident::whereNotNull('year')->distinct()->orderBy('year')->pluck('year'),
            ],
            // Wrapped in closures so an Inertia partial reload (e.g. changing just the
            // Occupancy filters) only recomputes the tab that's actually being requested,
            // instead of re-running all three tabs' queries on every filter change.
            'occupancy' => fn() => $this->occupancyData($request),
            'leaves' => fn() => $this->leavesData($request),
            'complaints' => fn() => $this->complaintsData($request),
        ]);
    }

    /**
     * Standalone JSON endpoint powering both:
     * - the "Occupancy Details of Unit: XS" modal (building + one room_type)
     * - the "Full building detail" inline heat-map (building, every room_type),
     *   when room_type is omitted entirely.
     * Floor-by-floor room list, colour-coded by how full each room is.
     */
    public function occupancyHeatmap(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'room_type' => 'nullable|string',
        ]);

        $query = Room::with('floor')->where('building_id', $validated['building_id']);

        if (!empty($validated['room_type'])) {
            $query->where('room_type', $validated['room_type']);
        }

        $rooms = $query->orderBy('floor_id')->orderBy('room_number')->get();

        $floors = $rooms->groupBy(fn($r) => $r->floor?->floor_number ?? 0)
            ->map(function ($roomsInFloor, $floorNumber) {
                return [
                    'floor_number' => $floorNumber,
                    'floor_name' => $roomsInFloor->first()->floor?->name ?? "Floor {$floorNumber}",
                    'rooms' => $roomsInFloor->map(fn($r) => [
                        'id' => $r->id,
                        'room_number' => $r->room_number,
                        'room_type' => $r->room_type,
                        'capacity' => $r->capacity,
                        'occupied_beds' => $r->occupied_beds,
                        'status' => $this->heatmapStatus($r),
                    ])->values(),
                ];
            })->sortBy('floor_number')->values();

        return response()->json(['floors' => $floors]);
    }

    protected function heatmapStatus(Room $room): string
    {
        if ($room->capacity <= 0) {
            return 'no_capacity';
        }
        if ($room->occupied_beds <= 0) {
            return 'vacant';
        }
        if ($room->occupied_beds >= $room->capacity) {
            return 'occupied';
        }

        return 'partially_filled';
    }

    // ------------------------------------------------------------------
    // Occupancy tab
    // ------------------------------------------------------------------
    protected function occupancyData(Request $request): array
    {
        $filters = $request->only(['gender', 'course', 'institute', 'batch', 'year']);

        $activeStays = ResidentStay::query()
            ->where('status', 'active')
            ->whereHas('resident', function ($q) use ($filters) {
                if (!empty($filters['gender'])) {
                    $q->where('gender', $filters['gender']);
                }
                if (!empty($filters['course'])) {
                    $q->where('course', $filters['course']);
                }
                if (!empty($filters['institute'])) {
                    $q->where('institute', $filters['institute']);
                }
                if (!empty($filters['batch'])) {
                    $q->where('batch', $filters['batch']);
                }
                if (!empty($filters['year'])) {
                    $q->where('year', $filters['year']);
                }
            })
            ->with('resident:id,gender,status')
            ->get(['id', 'resident_id', 'building_id', 'room_id', 'bed_id']);

        $totalCapacity = (int) Room::sum('capacity');
        $filledCapacity = $activeStays->count();
        $vacantCapacity = max(0, $totalCapacity - $filledCapacity);

        $roomWiseDistribution = [
            'fully_occupied' => Room::where('status', 'occupied')->count(),
            'vacant' => Room::where('status', 'available')->count(),
            'partially_filled' => Room::where('status', 'partially_occupied')->count(),
        ];

        $buildings = Building::orderBy('name')->get();

        $buildingWiseChart = $buildings->map(function ($building) use ($activeStays) {
            $staysInBuilding = $activeStays->where('building_id', $building->id);
            $capacity = (int) Room::where('building_id', $building->id)->sum('capacity');
            $occupied = (int) Room::where('building_id', $building->id)->sum('occupied_beds');

            return [
                'name' => $building->name,
                'building_id' => $building->id,
                'capacity' => $capacity,
                'occupied' => $occupied,
                'vacant' => max(0, $capacity - $occupied),
                'active_occupied' => $staysInBuilding->filter(fn($s) => $s->resident?->status === 'active')->count(),
                'suspended_occupied' => $staysInBuilding->filter(fn($s) => $s->resident?->status === 'suspended')->count(),
                'inactive_occupied' => $staysInBuilding->filter(fn($s) => $s->resident?->status === 'inactive')->count(),
            ];
        });

        // Room-wise (by room_type) rollup across every building — used for the
        // "Room-Wise" chart toggle and the "Unit-Wise Occupancy" expandable list.
        $roomTypes = Room::select('room_type')->distinct()->pluck('room_type');
        $unitWise = $roomTypes->map(function ($type) use ($buildings) {
            $roomsOfType = Room::where('room_type', $type)->get();
            $capacity = (int) $roomsOfType->sum('capacity');
            $occupied = (int) $roomsOfType->sum('occupied_beds');

            $byBuilding = $buildings->map(function ($b) use ($type) {
                $rooms = Room::where('room_type', $type)->where('building_id', $b->id)->get();
                return [
                    'building_id' => $b->id,
                    'name' => $b->name,
                    'capacity' => (int) $rooms->sum('capacity'),
                    'occupied' => (int) $rooms->sum('occupied_beds'),
                    'vacant' => (int) $rooms->sum('capacity') - (int) $rooms->sum('occupied_beds'),
                ];
            })->filter(fn($b) => $b['capacity'] > 0)->values();

            return [
                'room_type' => $type,
                'capacity' => $capacity,
                'occupied' => $occupied,
                'vacant' => max(0, $capacity - $occupied),
                'buildings' => $byBuilding,
            ];
        });

        // Bed-type breakdown table per building (Fully/Partially Occupied, Male/Female
        // Occupied, Vacant, Total students) — the table shown under a building card.
        $buildingBreakdown = $buildings->mapWithKeys(function ($building) use ($roomTypes, $activeStays) {
            $rows = $roomTypes->map(function ($type) use ($building, $activeStays) {
                $rooms = Room::where('building_id', $building->id)->where('room_type', $type)->get();
                if ($rooms->isEmpty()) {
                    return null;
                }

                $roomIds = $rooms->pluck('id');
                $staysInRooms = $activeStays->whereIn('room_id', $roomIds);

                return [
                    'room_type' => $type,
                    'total_rooms' => $rooms->count(),
                    'fully_occupied' => $rooms->filter(fn($r) => $r->occupied_beds >= $r->capacity && $r->capacity > 0)->count(),
                    'partially_occupied' => $rooms->filter(fn($r) => $r->occupied_beds > 0 && $r->occupied_beds < $r->capacity)->count(),
                    'male_occupied' => $staysInRooms->filter(fn($s) => $s->resident?->gender === 'male')->count(),
                    'female_occupied' => $staysInRooms->filter(fn($s) => $s->resident?->gender === 'female')->count(),
                    'vacant' => $rooms->filter(fn($r) => $r->occupied_beds <= 0)->count(),
                    'total_students' => (int) $rooms->sum('occupied_beds'),
                    // Bed-level figures for the "Bed-Wise" table view, as distinct from
                    // the room-level figures above used by "Room-Wise".
                    'total_beds' => (int) $rooms->sum('capacity'),
                    'occupied_beds' => (int) $rooms->sum('occupied_beds'),
                    'vacant_beds' => (int) $rooms->sum('capacity') - (int) $rooms->sum('occupied_beds'),
                ];
            })->filter()->values();

            return [$building->id => $rows];
        });

        return [
            'summary' => [
                'total_capacity' => $totalCapacity,
                'filled_capacity' => $filledCapacity,
                'vacant_capacity' => $vacantCapacity,
                'occupancy_percent' => $totalCapacity > 0 ? round(($filledCapacity / $totalCapacity) * 100) : 0,
            ],
            'room_wise_distribution' => $roomWiseDistribution,
            'building_wise_chart' => $buildingWiseChart,
            'unit_wise' => $unitWise,
            'buildings' => $buildings->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'capacity' => (int) Room::where('building_id', $b->id)->sum('capacity'),
                'occupied' => (int) Room::where('building_id', $b->id)->sum('occupied_beds'),
                'vacant' => (int) Room::where('building_id', $b->id)->sum('capacity') - (int) Room::where('building_id', $b->id)->sum('occupied_beds'),
            ]),
            'building_breakdown' => $buildingBreakdown,
            'room_types' => $roomTypes,
        ];
    }

    // ------------------------------------------------------------------
    // Leaves tab
    // ------------------------------------------------------------------
    protected function leavesData(Request $request): array
    {
        [$from, $to] = $this->resolveDateRange($request, 'leave_range', 'leave_from', 'leave_to');

        $query = LeaveRequest::query();
        if ($from && $to) {
            $query->whereBetween('from_date', [$from, $to]);
        }

        $leaves = $query->with('resident.currentStay.building')->get();

        $totalRequests = $leaves->count();
        $totalStudentsOnLeave = $leaves->pluck('resident_id')->unique()->count();

        $buildings = Building::orderBy('name')->get();
        $hostelWise = $buildings->map(function ($building) use ($leaves) {
            $forBuilding = $leaves->filter(fn($l) => $l->resident?->currentStay?->building_id === $building->id);

            return [
                'building_id' => $building->id,
                'name' => $building->name,
                'total_requests' => $forBuilding->count(),
                'students_on_leave' => $forBuilding->pluck('resident_id')->unique()->count(),
            ];
        })->filter(fn($b) => $b['total_requests'] > 0)->values();

        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $frequency = collect($dayNames)->map(function ($day) use ($leaves) {
            $count = $leaves->filter(fn($l) => Carbon::parse($l->from_date)->format('l') === $day)->count();
            return ['day' => $day, 'count' => $count];
        });

        return [
            'total_requests' => $totalRequests,
            'total_students_on_leave' => $totalStudentsOnLeave,
            'hostel_wise' => $hostelWise,
            'frequency' => $frequency,
        ];
    }

    // ------------------------------------------------------------------
    // Complaints tab
    // ------------------------------------------------------------------
    protected function complaintsData(Request $request): array
    {
        [$from, $to] = $this->resolveDateRange($request, 'complaint_range', 'complaint_from', 'complaint_to');

        $query = Complaint::query();
        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        $complaints = $query->get();

        $raised = $complaints->count();
        $resolved = $complaints->where('status', 'resolved')->count();
        $pending = $complaints->whereIn('status', ['open', 'in_progress', 'escalated'])->count();
        $rejected = $complaints->where('status', 'rejected')->count();

        $buildings = Building::orderBy('name')->get();
        $hostelWise = $buildings->map(function ($building) use ($complaints) {
            $forBuilding = $complaints->where('building_id', $building->id);
            $r = $forBuilding->count();
            $res = $forBuilding->where('status', 'resolved')->count();

            return [
                'building_id' => $building->id,
                'name' => $building->name,
                'raised' => $r,
                'resolved' => $res,
                'pending' => $forBuilding->whereIn('status', ['open', 'in_progress', 'escalated'])->count(),
                'rejected' => $forBuilding->where('status', 'rejected')->count(),
                'success_rate' => $r > 0 ? round(($res / $r) * 100) : 0,
            ];
        });

        $byPriority = collect(['urgent', 'high', 'medium', 'low'])->map(fn($p) => [
            'priority' => $p,
            'count' => $complaints->where('priority', $p)->count(),
        ]);

        return [
            'raised' => $raised,
            'resolved' => $resolved,
            'pending' => $pending,
            'rejected' => $rejected,
            'success_rate' => $raised > 0 ? round(($resolved / $raised) * 100) : 0,
            'hostel_wise' => $hostelWise,
            'by_priority' => $byPriority,
        ];
    }

    /**
     * Shared date-range resolver for the preset pills used on Leaves/Complaints
     * (All, Today, Current Week, Last 7 days, Last week, This month, Last month,
     * Last 30 days, Custom Date).
     */
    protected function resolveDateRange(Request $request, string $presetKey, string $fromKey, string $toKey): array
    {
        $preset = $request->string($presetKey)->toString() ?: 'all';

        if ($preset === 'custom') {
            $from = $request->date($fromKey);
            $to = $request->date($toKey);
            return [$from, $to];
        }

        $now = Carbon::now();

        return match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'current_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            default => [null, null], // 'all' — no date restriction
        };
    }
}
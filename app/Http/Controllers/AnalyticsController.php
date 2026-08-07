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

        $forecastDate = $request->date('forecast_date') ?? Carbon::today();

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
            'forecast' => $this->occupancyForecastData(
                $request,
                $forecastDate
            ),
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

    /**
     * Forecast occupancy for a specific date.
     *
     * Rules:
     * - check_in_date must be on or before the forecast date.
     * - actual_check_out_date takes priority when it exists.
     * - otherwise expected_check_out_date is used.
     * - The checkout date itself is still considered occupied.
     * - If there is no checkout date, the stay remains occupied.
     */
    protected function occupancyForecastData(
        Request $request,
        Carbon $forecastDate
    ): array {
        $forecastDate = $forecastDate->copy()->startOfDay();

        $stays = ResidentStay::query()
            ->with([
                'resident:id,first_name,last_name,gender,status',
                'building:id,name',
                'room:id,room_number,room_type,capacity',
                'bed:id,bed_number,room_id,status',
            ])
            ->whereDate('check_in_date', '<=', $forecastDate)
            ->where(function ($query) use ($forecastDate) {
                $query
                    // Actual checkout exists:
                    // occupied THROUGH the actual checkout date.
                    ->whereDate(
                        'actual_check_out_date',
                        '>=',
                        $forecastDate
                    )

                    // No actual checkout:
                    // use expected checkout date.
                    ->orWhere(function ($q) use ($forecastDate) {
                        $q->whereNull('actual_check_out_date')
                            ->where(function ($q2) use ($forecastDate) {
                                // Expected checkout exists and is
                                // on/after forecast date.
                                $q2->whereDate(
                                    'expected_check_out_date',
                                    '>=',
                                    $forecastDate
                                )

                                    // No expected checkout means
                                    // indefinitely occupied.
                                    ->orWhereNull(
                                        'expected_check_out_date'
                                    );
                            });
                    });
            })
            ->whereIn('status', ['upcoming', 'active'])
            ->get();

        $buildings = Building::query()
            ->with([
                'floors' => function ($floorQuery) {
                    $floorQuery
                        ->orderBy('floor_number')
                        ->with([
                            'rooms' => function ($roomQuery) {
                                $roomQuery
                                    ->orderBy('room_number')
                                    ->with([
                                        'beds' => function ($bedQuery) {
                                            $bedQuery->orderBy('bed_number');
                                        },
                                    ]);
                            },
                        ]);
                },
            ])
            ->orderBy('name')
            ->get();

        $occupiedByBed = $stays->keyBy('bed_id');

        $occupiedByRoom = $stays
            ->groupBy('room_id');

        $occupiedByBuilding = $stays
            ->groupBy('building_id');

        return [
            'forecast_date' => $forecastDate->toDateString(),

            'summary' => [
                'total_buildings' => $buildings->count(),

                'total_rooms' => $buildings
                    ->sum(
                        fn($building) =>
                        $building->floors->sum(
                            fn($floor) => $floor->rooms->count()
                        )
                    ),

                'total_beds' => $buildings
                    ->sum(
                        fn($building) =>
                        $building->floors->sum(
                            fn($floor) =>
                            $floor->rooms->sum(
                                fn($room) => $room->capacity
                            )
                        )
                    ),

                'occupied_beds' => $stays->count(),

                'vacant_beds' => max(
                    0,
                    $buildings->sum(
                        fn($building) =>
                        $building->floors->sum(
                            fn($floor) =>
                            $floor->rooms->sum(
                                fn($room) => $room->capacity
                            )
                        )
                    ) - $stays->count()
                ),
            ],

            'buildings' => $buildings->map(
                function ($building) use ($occupiedByBed, $occupiedByRoom, $occupiedByBuilding) {
                    $buildingBeds = $building->floors->sum(
                        fn($floor) =>
                        $floor->rooms->sum(
                            fn($room) => $room->capacity
                        )
                    );

                    $buildingOccupied =
                        $occupiedByBuilding->get($building->id, collect())->count();

                    return [
                        'id' => $building->id,
                        'name' => $building->name,

                        'capacity' => $buildingBeds,

                        'occupied_beds' => $buildingOccupied,

                        'vacant_beds' => max(
                            0,
                            $buildingBeds - $buildingOccupied
                        ),

                        'floors' => $building->floors->map(
                            function ($floor) use ($occupiedByBed, $occupiedByRoom) {
                                return [
                                    'id' => $floor->id,
                                    'name' => $floor->name,
                                    'floor_number' => $floor->floor_number,

                                    'rooms' => $floor->rooms->map(
                                        function ($room) use ($occupiedByBed, $occupiedByRoom) {
                                            $roomStays =
                                                $occupiedByRoom->get(
                                                    $room->id,
                                                    collect()
                                                );

                                            $occupiedBeds =
                                                $roomStays->count();

                                            return [
                                                'id' => $room->id,

                                                'room_number' =>
                                                    $room->room_number,

                                                'room_type' =>
                                                    $room->room_type,

                                                'capacity' =>
                                                    (int) $room->capacity,

                                                'occupied_beds' =>
                                                    $occupiedBeds,

                                                'vacant_beds' =>
                                                    max(
                                                        0,
                                                        (int) $room->capacity
                                                        - $occupiedBeds
                                                    ),

                                                'status' =>
                                                    $occupiedBeds <= 0
                                                    ? 'vacant'
                                                    : (
                                                        $occupiedBeds >=
                                                        $room->capacity
                                                        ? 'occupied'
                                                        : 'partially_occupied'
                                                    ),

                                                'beds' =>
                                                    $room->beds->map(
                                                        function ($bed) use ($occupiedByBed) {
                                                            $stay =
                                                                $occupiedByBed->get(
                                                                    $bed->id
                                                                );

                                                            return [
                                                                'id' =>
                                                                    $bed->id,

                                                                'bed_number' =>
                                                                    $bed->bed_number,

                                                                'status' =>
                                                                    $stay
                                                                    ? 'occupied'
                                                                    : 'vacant',

                                                                'stay' =>
                                                                    $stay
                                                                    ? [
                                                                        'stay_id' =>
                                                                            $stay->id,

                                                                        'resident_id' =>
                                                                            $stay->resident_id,

                                                                        'resident_name' =>
                                                                            trim(
                                                                                ($stay->resident?->first_name ?? '')
                                                                                . ' ' .
                                                                                ($stay->resident?->last_name ?? '')
                                                                            ),

                                                                        'gender' =>
                                                                            $stay->resident?->gender,

                                                                        'check_in_date' =>
                                                                            $stay->check_in_date,

                                                                        'expected_check_out_date' =>
                                                                            $stay->expected_check_out_date,

                                                                        'actual_check_out_date' =>
                                                                            $stay->actual_check_out_date,
                                                                    ]
                                                                    : null,
                                                            ];
                                                        }
                                                    )->values(),
                                            ];
                                        }
                                    )->values(),
                                ];
                            }
                        )->values(),
                    ];
                }
            )->values(),
        ];
    }

    /**
     * Forecast room/bed occupancy for a selected date.
     *
     * Rules:
     * - A stay is relevant only if it has started by the selected date.
     * - If actual_check_out_date exists, that is the real checkout date.
     * - Otherwise expected_check_out_date is used.
     * - If expected checkout is ON the selected date, the resident is
     *   still considered occupied for that date.
     * - If checkout is BEFORE the selected date, the bed is available.
     * - No checkout date means the stay continues indefinitely.
     */

    public function occupancyForecast(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $forecastDate = Carbon::parse($validated['date'])->startOfDay();

        /*
         * ------------------------------------------------------------
         * 1. Get stays which are occupied on the forecast date
         * ------------------------------------------------------------
         *
         * Rules:
         *
         * check_in_date <= forecast date
         *
         * Actual checkout exists:
         *     actual_checkout >= forecast date
         *
         * No actual checkout:
         *     expected_checkout >= forecast date
         *     OR expected_checkout is NULL
         *
         * Therefore:
         *
         * checkout = 7 Aug
         * forecast = 7 Aug
         * => OCCUPIED
         *
         * checkout = 7 Aug
         * forecast = 8 Aug
         * => VACANT
         */

        $stays = ResidentStay::query()
            ->with([
                'resident:id,first_name,last_name,gender,status',
                'building:id,name',
                'room:id,room_number,room_type,capacity',
                'bed:id,bed_number,room_id,status',
            ])
            ->whereDate('check_in_date', '<=', $forecastDate)

            ->where(function ($query) use ($forecastDate) {

                /*
                 * Actual checkout exists.
                 *
                 * Stay remains occupied THROUGH the actual
                 * checkout date.
                 */
                $query
                    ->whereDate(
                        'actual_check_out_date',
                        '>=',
                        $forecastDate
                    )

                    /*
                     * No actual checkout.
                     *
                     * Use expected checkout date.
                     */
                    ->orWhere(function ($q) use ($forecastDate) {

                        $q->whereNull('actual_check_out_date')

                            ->where(function ($q2) use ($forecastDate) {

                                /*
                                 * Expected checkout exists and
                                 * is on/after forecast date.
                                 */
                                $q2->whereDate(
                                    'expected_check_out_date',
                                    '>=',
                                    $forecastDate
                                )

                                    /*
                                     * No expected checkout.
                                     *
                                     * Consider indefinitely occupied.
                                     */
                                    ->orWhereNull(
                                        'expected_check_out_date'
                                    );
                            });
                    });
            })

            /*
             * Only these stays participate in forecast occupancy.
             */
            ->whereIn('status', [
                'upcoming',
                'active',
            ])

            ->get();


        /*
         * ------------------------------------------------------------
         * 2. Load the actual hierarchy
         * ------------------------------------------------------------
         *
         * Building
         *     -> Floors
         *          -> Rooms
         *               -> Beds
         *
         * We are NOT assuming building.rooms exists.
         */
        $buildings = Building::query()
            ->with([
                'floors' => function ($floorQuery) {

                    $floorQuery
                        ->orderBy('floor_number')
                        ->with([
                            'rooms' => function ($roomQuery) {

                                $roomQuery
                                    ->orderBy('room_number')
                                    ->with([
                                        'beds' => function ($bedQuery) {

                                            $bedQuery
                                                ->orderBy('bed_number');
                                        },
                                    ]);
                            },
                        ]);
                },
            ])
            ->orderBy('name')
            ->get();


        /*
         * ------------------------------------------------------------
         * 3. Index occupied stays
         * ------------------------------------------------------------
         *
         * This allows us to quickly determine whether a particular
         * bed is occupied on the forecast date.
         */
        $occupiedByBed = $stays->keyBy('bed_id');


        /*
         * Group stays by room for room-level calculations.
         */
        $occupiedByRoom = $stays->groupBy('room_id');


        /*
         * Group stays by building for building-level calculations.
         */
        $occupiedByBuilding = $stays->groupBy('building_id');


        /*
         * ------------------------------------------------------------
         * 4. Calculate summary
         * ------------------------------------------------------------
         */

        $totalRooms = $buildings->sum(
            fn($building) =>
            $building->floors->sum(
                fn($floor) =>
                $floor->rooms->count()
            )
        );

        $totalBeds = $buildings->sum(
            fn($building) =>
            $building->floors->sum(
                fn($floor) =>
                $floor->rooms->sum(
                    fn($room) =>
                    (int) $room->capacity
                )
            )
        );

        $occupiedBeds = $stays->count();

        $vacantBeds = max(
            0,
            $totalBeds - $occupiedBeds
        );


        /*
         * ------------------------------------------------------------
         * 5. Build final response
         * ------------------------------------------------------------
         */

        return response()->json([

            'forecast_date' => $forecastDate->toDateString(),

            'summary' => [

                'total_buildings' => $buildings->count(),

                'total_rooms' => $totalRooms,

                'total_beds' => $totalBeds,

                'occupied_beds' => $occupiedBeds,

                'vacant_beds' => $vacantBeds,
            ],


            /*
             * --------------------------------------------------------
             * Building
             *     -> Floor
             *          -> Room
             *               -> Bed
             * --------------------------------------------------------
             */
            'buildings' => $buildings->map(
                function ($building) use ($occupiedByBed, $occupiedByRoom, $occupiedByBuilding) {

                    /*
                     * Building capacity.
                     */
                    $buildingBeds = $building->floors->sum(
                        fn($floor) =>
                        $floor->rooms->sum(
                            fn($room) =>
                            (int) $room->capacity
                        )
                    );


                    /*
                     * Occupied beds in this building.
                     */
                    $buildingOccupied =
                        $occupiedByBuilding
                            ->get($building->id, collect())
                            ->count();


                    return [

                        'id' => $building->id,

                        'name' => $building->name,

                        'capacity' => (int) $buildingBeds,

                        'occupied_beds' => (int) $buildingOccupied,

                        'vacant_beds' => max(
                            0,
                            (int) $buildingBeds - $buildingOccupied
                        ),


                        /*
                         * ------------------------------------------------
                         * Floors
                         * ------------------------------------------------
                         */
                        'floors' => $building->floors->map(

                            function ($floor) use ($occupiedByBed, $occupiedByRoom) {

                                return [

                                    'id' => $floor->id,

                                    'name' => $floor->name,

                                    'floor_number' =>
                                        $floor->floor_number,


                                    /*
                                     * ------------------------------------
                                     * Rooms
                                     * ------------------------------------
                                     */
                                    'rooms' => $floor->rooms->map(

                                        function ($room) use ($occupiedByBed, $occupiedByRoom) {

                                            /*
                                             * Get stays occupying beds
                                             * belonging to this room.
                                             */
                                            $roomStays =
                                                $occupiedByRoom->get(
                                                    $room->id,
                                                    collect()
                                                );


                                            $occupiedBeds =
                                                $roomStays->count();


                                            $capacity =
                                                (int) $room->capacity;


                                            $vacantBeds = max(
                                                0,
                                                $capacity - $occupiedBeds
                                            );


                                            /*
                                             * Room status.
                                             */
                                            $status = match (true) {

                                                $occupiedBeds <= 0 =>
                                                'vacant',

                                                $occupiedBeds >= $capacity =>
                                                'occupied',

                                                default =>
                                                'partially_occupied',
                                            };


                                            return [

                                                'id' => $room->id,

                                                'room_number' =>
                                                    $room->room_number,

                                                'room_type' =>
                                                    $room->room_type,

                                                'capacity' =>
                                                    $capacity,

                                                'occupied_beds' =>
                                                    $occupiedBeds,

                                                'vacant_beds' =>
                                                    $vacantBeds,

                                                'status' =>
                                                    $status,


                                                /*
                                                 * --------------------------------
                                                 * Beds
                                                 * --------------------------------
                                                 */
                                                'beds' => $room->beds->map(

                                                    function ($bed) use ($occupiedByBed) {

                                                        $stay =
                                                            $occupiedByBed->get(
                                                                $bed->id
                                                            );


                                                        /*
                                                         * No stay means
                                                         * this bed is vacant
                                                         * on forecast date.
                                                         */
                                                        if (!$stay) {

                                                            return [

                                                                'id' =>
                                                                    $bed->id,

                                                                'bed_number' =>
                                                                    $bed->bed_number,

                                                                'status' =>
                                                                    'vacant',

                                                                'stay' =>
                                                                    null,
                                                            ];
                                                        }


                                                        /*
                                                         * Bed is occupied.
                                                         */
                                                        return [

                                                            'id' =>
                                                                $bed->id,

                                                            'bed_number' =>
                                                                $bed->bed_number,

                                                            'status' =>
                                                                'occupied',


                                                            'stay' => [

                                                                'stay_id' =>
                                                                    $stay->id,

                                                                'resident_id' =>
                                                                    $stay->resident_id,

                                                                'resident_name' =>
                                                                    trim(
                                                                        ($stay->resident?->first_name ?? '')
                                                                        . ' ' .
                                                                        ($stay->resident?->last_name ?? '')
                                                                    ),

                                                                'gender' =>
                                                                    $stay->resident?->gender,

                                                                'resident_status' =>
                                                                    $stay->resident?->status,

                                                                'check_in_date' =>
                                                                    optional(
                                                                        $stay->check_in_date
                                                                    )->toDateString(),

                                                                'expected_check_out_date' =>
                                                                    optional(
                                                                        $stay->expected_check_out_date
                                                                    )->toDateString(),

                                                                'actual_check_out_date' =>
                                                                    optional(
                                                                        $stay->actual_check_out_date
                                                                    )->toDateString(),

                                                                /*
                                                                 * Useful for
                                                                 * debugging / UI.
                                                                 *
                                                                 * Actual checkout
                                                                 * has priority.
                                                                 */
                                                                'checkout_date_used' =>
                                                                    $stay->actual_check_out_date
                                                                    ? Carbon::parse(
                                                                        $stay->actual_check_out_date
                                                                    )->toDateString()
                                                                    : (
                                                                        $stay->expected_check_out_date
                                                                        ? Carbon::parse(
                                                                            $stay->expected_check_out_date
                                                                        )->toDateString()
                                                                        : null
                                                                    ),
                                                            ],
                                                        ];
                                                    }

                                                )->values(),
                                            ];
                                        }

                                    )->values(),
                                ];
                            }

                        )->values(),
                    ];
                }

            )->values(),
        ]);
    }


    protected function leavesData(Request $request): array
    {
        [$from, $to] = $this->resolveDateRange(
            $request,
            'leave_range',
            'leave_from',
            'leave_to'
        );

        /*
         * All requests whose leave start date falls in the selected period.
         */
        $query = LeaveRequest::query()
            ->with([
                'resident.currentStay.building',
            ]);

        if ($from && $to) {
            $query->whereDate('from_date', '>=', $from)
                ->whereDate('from_date', '<=', $to);
        }


        $leaves = $query->get();

        /*
         * Requests by final status.
         */
        $approvedLeaves = $leaves
            ->where('final_status', 'approved')
            ->values();

        $pendingLeaves = $leaves
            ->whereIn('final_status', [
                'pending',
                'parent_approval_pending',
            ])
            ->values();

        $rejectedLeaves = $leaves
            ->where('final_status', 'rejected')
            ->values();

        $cancelledLeaves = $leaves
            ->where('final_status', 'cancelled')
            ->values();

        $expiredLeaves = $leaves
            ->where('final_status', 'expired')
            ->values();

        /*
         * Currently on leave must always mean:
         *
         * 1. Leave is approved.
         * 2. Today's date lies between from_date and to_date.
         *
         * This query is intentionally independent of the selected analytics
         * period, because "currently on leave" should always represent today.
         */
        $today = now()->toDateString();

        $currentlyOnLeave = LeaveRequest::query()
            ->with([
                'resident.currentStay.building',
            ])
            ->where('final_status', 'approved')
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->get();

        $totalRequests = $leaves->count();

        $approvedCount = $approvedLeaves->count();

        $pendingCount = $pendingLeaves->count();

        $rejectedCount = $rejectedLeaves->count();

        $cancelledCount = $cancelledLeaves->count();

        $expiredCount = $expiredLeaves->count();

        $currentlyOnLeaveCount = $currentlyOnLeave
            ->pluck('resident_id')
            ->unique()
            ->count();

        /*
         * Approval percentage based on resolved requests only.
         *
         * Pending requests are excluded because no final decision has
         * been made yet.
         */
        $resolvedCount =
            $approvedCount
            + $rejectedCount
            + $cancelledCount
            + $expiredCount;

        $approvalRate = $resolvedCount > 0
            ? round(
                ($approvedCount / $resolvedCount) * 100,
                1
            )
            : 0;

        /*
         * Building-wise analytics for the selected period.
         */
        $buildings = Building::query()
            ->orderBy('name')
            ->get();

        $hostelWise = $buildings
            ->map(function (Building $building) use ($leaves, $approvedLeaves, $pendingLeaves, $currentlyOnLeave) {
                $requestsForBuilding = $leaves
                    ->filter(
                        fn(LeaveRequest $leave) =>
                        (int) (
                            $leave
                                ->resident
                                ?->currentStay
                                    ?->building_id
                        ) === (int) $building->id
                    );

                $approvedForBuilding = $approvedLeaves
                    ->filter(
                        fn(LeaveRequest $leave) =>
                        (int) (
                            $leave
                                ->resident
                                ?->currentStay
                                    ?->building_id
                        ) === (int) $building->id
                    );

                $pendingForBuilding = $pendingLeaves
                    ->filter(
                        fn(LeaveRequest $leave) =>
                        (int) (
                            $leave
                                ->resident
                                ?->currentStay
                                    ?->building_id
                        ) === (int) $building->id
                    );

                $currentlyOnLeaveForBuilding =
                    $currentlyOnLeave->filter(
                        fn(LeaveRequest $leave) =>
                        (int) (
                            $leave
                                ->resident
                                ?->currentStay
                                    ?->building_id
                        ) === (int) $building->id
                    );

                return [
                    'building_id' =>
                        $building->id,

                    'name' =>
                        $building->name,

                    'total_requests' =>
                        $requestsForBuilding->count(),

                    'approved_leaves' =>
                        $approvedForBuilding->count(),

                    'pending_requests' =>
                        $pendingForBuilding->count(),

                    'currently_on_leave' =>
                        $currentlyOnLeaveForBuilding
                            ->pluck('resident_id')
                            ->unique()
                            ->count(),
                ];
            })
            ->filter(
                fn(array $building) =>
                $building['total_requests'] > 0
                || $building['currently_on_leave'] > 0
            )
            ->values();

        /*
         * Leave frequency should count actual approved leaves only.
         *
         * Rejected, pending and cancelled requests must not influence
         * behavioural analytics.
         */
        $dayNames = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ];

        $frequency = collect($dayNames)->map(function ($day) use ($leaves) {
            $count = $leaves->filter(fn($l) => Carbon::parse($l->from_date)->format('l') === $day)->count();
            return ['day' => $day, 'count' => $count];
        });

        /*
         * Leave types should also represent approved leaves.
         */
        $leaveTypes = collect([
            'home_leave' => 'Home Leave',
            'medical_leave' => 'Medical Leave',
            'emergency_leave' => 'Emergency Leave',
            'day_out' => 'Day Out',
            'night_pass' => 'Night Pass',
        ])->map(function (string $label, string $type) use ($approvedLeaves) {
            return [
                'type' => $type,
                'label' => $label,
                'count' => $approvedLeaves
                    ->where('leave_type', $type)
                    ->count(),
            ];
        })->values();

        return [
            'total_requests' =>
                $totalRequests,

            'approved_leaves' =>
                $approvedCount,

            'pending_requests' =>
                $pendingCount,

            'rejected_requests' =>
                $rejectedCount,

            'cancelled_requests' =>
                $cancelledCount,

            'expired_requests' =>
                $expiredCount,

            'currently_on_leave' =>
                $currentlyOnLeaveCount,

            'approval_rate' =>
                $approvalRate,

            'hostel_wise' =>
                $hostelWise,

            'frequency' =>
                $frequency,

            'leave_types' =>
                $leaveTypes,
        ];
    }

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
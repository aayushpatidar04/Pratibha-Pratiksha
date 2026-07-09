<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Resident;
use App\Models\Room;
use App\Models\RoomChangeRequest;
use App\Services\RoomAllotmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomChangeRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $query = RoomChangeRequest::with([
            'resident',
            'currentStay.room',
            'currentStay.building',
            'requestedBuilding',
            'requestedFloor',
            'requestedRoom',
            'requestedBed',
        ]);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $requests = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return Inertia::render('Residents/RoomChangeRequests/Index', [
            'requests' => $requests,
            'stats' => [
                'pending' => RoomChangeRequest::where('status', 'pending')->count(),
                'approved' => RoomChangeRequest::where('status', 'approved')->count(),
                'rejected' => RoomChangeRequest::where('status', 'rejected')->count(),
            ],
            'filters' => $request->only('status'),
            'residents' => Resident::where('status', 'active')->with('currentStay')->orderBy('first_name')->get()
                ->map(fn($r) => ['id' => $r->id, 'name' => trim("{$r->first_name} {$r->last_name}"), 'resident_code' => $r->resident_code, 'current_stay_id' => $r->currentStay?->id]),
            'buildings' => Building::orderBy('name')->get(['id', 'name']),
            'floors' => Floor::orderBy('floor_number')->get(['id', 'name', 'building_id']),
            'rooms' => Room::with('beds')->orderBy('room_number')->get(['id', 'room_number', 'building_id', 'floor_id', 'capacity', 'occupied_beds']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'reason' => 'nullable|string',
            'requested_building_id' => 'nullable|exists:buildings,id',
            'requested_floor_id' => 'nullable|exists:floors,id',
            'requested_room_id' => 'nullable|exists:rooms,id',
            'requested_bed_id' => 'nullable|exists:beds,id',
        ]);

        $resident = Resident::with('currentStay')->findOrFail($validated['resident_id']);
        $validated['current_stay_id'] = $resident->currentStay?->id;
        $validated['status'] = 'pending';
        $validated['requested_by'] = $request->user()?->id;

        RoomChangeRequest::create($validated);

        return back()->with('success', 'Room change request submitted.');
    }

    /**
     * Approve = actually move the resident: check them out of their current bed (if
     * any) and allot the requested bed, via the same RoomAllotmentService used by
     * Check-In/Check-Out — so occupancy counters stay correct everywhere.
     */
    public function approve(Request $request, RoomChangeRequest $roomChangeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string',
        ]);

        if (!$roomChangeRequest->requested_bed_id) {
            return back()->with('error', 'Pick a specific bed for this request before approving it.');
        }

        $resident = $roomChangeRequest->resident;

        if ($roomChangeRequest->currentStay && $roomChangeRequest->currentStay->status === 'active') {
            RoomAllotmentService::checkout($roomChangeRequest->currentStay);
        }

        try {
            RoomAllotmentService::allot($resident, [
                'building_id' => $roomChangeRequest->requested_building_id,
                'floor_id' => $roomChangeRequest->requested_floor_id,
                'room_id' => $roomChangeRequest->requested_room_id,
                'bed_id' => $roomChangeRequest->requested_bed_id,
            ]);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $roomChangeRequest->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        return back()->with('success', 'Room change approved and resident moved.');
    }

    public function reject(Request $request, RoomChangeRequest $roomChangeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string',
        ]);

        $roomChangeRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        return back()->with('success', 'Room change request rejected.');
    }
}
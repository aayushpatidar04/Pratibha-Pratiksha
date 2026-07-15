<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Resident;
use App\Models\Room;
use App\Models\RoomChangeRequest;
use App\Services\RoomAllotmentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
    public function approve(
        Request $request,
        RoomChangeRequest $roomChangeRequest
    ): RedirectResponse {
        $currentStay = $roomChangeRequest->currentStay;

        $validated = $request->validate([
            'effective_from' => [
                'required',
                'date',
                $currentStay
                ? 'after_or_equal:' .
                $currentStay->check_in_date->toDateString()
                : 'date',
            ],

            'new_billing_basis' => [
                'required',
                Rule::in([
                    'monthly',
                    'daily',
                ]),
            ],

            'new_rent_amount' => [
                'nullable',
                'required_if:new_billing_basis,monthly',
                'numeric',
                'min:0',
            ],

            'new_daily_rate' => [
                'nullable',
                'required_if:new_billing_basis,daily',
                'numeric',
                'min:0.01',
            ],

            'new_expected_check_out_date' => [
                'nullable',
                'required_if:new_billing_basis,daily',
                'date',
                'after_or_equal:effective_from',
            ],

            'admin_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        if ($roomChangeRequest->status !== 'pending') {
            return back()->with(
                'error',
                'This request has already been reviewed.'
            );
        }

        if (!$roomChangeRequest->requested_bed_id) {
            return back()->with(
                'error',
                'Select a specific bed before approving the request.'
            );
        }

        if (!$currentStay) {
            return back()->with(
                'error',
                'The resident does not have a current stay.'
            );
        }

        try {
            $newStay = RoomAllotmentService::transferRoom(
                currentStay: $currentStay,
                data: [
                    'building_id' =>
                        $roomChangeRequest->requested_building_id,

                    'floor_id' =>
                        $roomChangeRequest->requested_floor_id,

                    'room_id' =>
                        $roomChangeRequest->requested_room_id,

                    'bed_id' =>
                        $roomChangeRequest->requested_bed_id,

                    'effective_from' =>
                        $validated['effective_from'],

                    'billing_basis' =>
                        $validated['new_billing_basis'],

                    'rent_amount' =>
                        $validated['new_rent_amount'] ?? null,

                    'daily_rate' =>
                        $validated['new_daily_rate'] ?? null,

                    'expected_check_out_date' =>
                        $validated[
                            'new_expected_check_out_date'
                        ] ?? null,

                    'notes' =>
                        $validated['admin_notes'] ?? null,

                    'transferred_by' =>
                        $request->user()?->id,
                ]
            );
        } catch (\RuntimeException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }

        $roomChangeRequest->update([
            'status' => 'approved',
            'effective_from' => $validated['effective_from'],
            'new_billing_basis' =>
                $validated['new_billing_basis'],
            'new_rent_amount' =>
                $validated['new_rent_amount'] ?? null,
            'new_daily_rate' =>
                $validated['new_daily_rate'] ?? null,
            'new_expected_check_out_date' =>
                $validated[
                    'new_expected_check_out_date'
                ] ?? null,
            'new_stay_id' => $newStay->id,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'admin_notes' =>
                $validated['admin_notes'] ?? null,
        ]);

        return back()->with(
            'success',
            'Room change approved. The resident has been transferred and future billing will use the new room charges.'
        );
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
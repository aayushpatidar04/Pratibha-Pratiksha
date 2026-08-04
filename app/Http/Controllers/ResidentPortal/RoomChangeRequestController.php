<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Resident;
use App\Models\Room;
use App\Models\RoomChangeRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RoomChangeRequestController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:all,pending,approved,rejected,cancelled',
            ],

            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $filters = [
            'status' => $validated['status'] ?? 'all',
            'search' => trim(
                $validated['search'] ?? ''
            ),
        ];

        $baseQuery = RoomChangeRequest::query()
            ->where('resident_id', $resident->id);

        $requests = (clone $baseQuery)
            ->with([
                'currentStay.building:id,name',
                'currentStay.floor:id,name',
                'currentStay.room:id,room_number',
                'currentStay.bed:id,bed_number',

                'requestedBuilding:id,name',
                'requestedFloor:id,name',
                'requestedRoom:id,room_number,monthly_rent_per_bed',
                'requestedBed:id,bed_number,status',

                'newStay.building:id,name',
                'newStay.room:id,room_number',
                'newStay.bed:id,bed_number',

                'reviewedBy:id,name',
            ])
            ->when(
                $filters['status'] !== 'all',
                fn (Builder $query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                $filters['search'] !== '',
                function (
                    Builder $query
                ) use ($filters): void {
                    $search = $filters['search'];

                    $query->where(
                        function (
                            Builder $query
                        ) use ($search): void {
                            $query
                                ->where(
                                    'reason',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'admin_notes',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'requestedRoom',
                                    fn (Builder $roomQuery) =>
                                        $roomQuery->where(
                                            'room_number',
                                            'like',
                                            "%{$search}%"
                                        )
                                )
                                ->orWhereHas(
                                    'requestedBuilding',
                                    fn (Builder $buildingQuery) =>
                                        $buildingQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                );
                        }
                    );
                }
            )
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(
                fn (RoomChangeRequest $changeRequest) =>
                    $this->transformRequest(
                        $changeRequest
                    )
            );

        $stats = [
            'total' => (clone $baseQuery)->count(),

            'pending' => (clone $baseQuery)
                ->where('status', 'pending')
                ->count(),

            'approved' => (clone $baseQuery)
                ->where('status', 'approved')
                ->count(),

            'rejected' => (clone $baseQuery)
                ->where('status', 'rejected')
                ->count(),

            'cancelled' => (clone $baseQuery)
                ->where('status', 'cancelled')
                ->count(),
        ];

        $resident->load([
            'currentStay.building:id,name',
            'currentStay.floor:id,name',
            'currentStay.room:id,room_number,monthly_rent_per_bed',
            'currentStay.bed:id,bed_number',
        ]);

        $currentStay = $resident->currentStay;

        return Inertia::render(
            'ResidentPortal/RoomChangeRequests/Index',
            [
                'requests' => $requests,
                'stats' => $stats,
                'filters' => $filters,

                'currentStay' => $currentStay
                    ? [
                        'id' => $currentStay->id,

                        'building_id' =>
                            $currentStay->building_id,

                        'building_name' =>
                            $currentStay->building?->name,

                        'floor_id' =>
                            $currentStay->floor_id,

                        'floor_name' =>
                            $currentStay->floor?->name,

                        'room_id' =>
                            $currentStay->room_id,

                        'room_number' =>
                            $currentStay->room?->room_number,

                        'bed_id' =>
                            $currentStay->bed_id,

                        'bed_number' =>
                            $currentStay->bed?->bed_number,

                        'billing_basis' =>
                            $currentStay->billing_basis,

                        'rent_amount' =>
                            (float) $currentStay->rent_amount,

                        'daily_rate' =>
                            $currentStay->daily_rate !== null
                                ? (float) $currentStay->daily_rate
                                : null,
                    ]
                    : null,

                'hasPendingRequest' =>
                    (clone $baseQuery)
                        ->where('status', 'pending')
                        ->exists(),

                'buildings' => Building::query()
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                    ]),

                'floors' => Floor::query()
                    ->orderBy('floor_number')
                    ->get([
                        'id',
                        'name',
                        'building_id',
                    ]),

                'rooms' => Room::query()
                    ->with([
                        'beds:id,room_id,bed_number,status',
                    ])
                    ->whereIn('status', [
                        'available',
                        'partially_occupied',
                    ])
                    ->orderBy('room_number')
                    ->get([
                        'id',
                        'building_id',
                        'floor_id',
                        'room_number',
                        'room_type',
                        'capacity',
                        'occupied_beds',
                        'monthly_rent_per_bed',
                        'status',
                    ]),
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:3000',
            ],

            'requested_building_id' => [
                'required',
                'exists:buildings,id',
            ],

            'requested_floor_id' => [
                'required',
                'exists:floors,id',
            ],

            'requested_room_id' => [
                'required',
                'exists:rooms,id',
            ],

            'requested_bed_id' => [
                'required',
                'exists:beds,id',
            ],
        ]);

        $resident->load('currentStay');

        $currentStay = $resident->currentStay;

        if (!$currentStay) {
            return back()->with(
                'error',
                'You do not have an active stay, so a room-change request cannot be submitted.'
            );
        }

        $hasPendingRequest = RoomChangeRequest::query()
            ->where('resident_id', $resident->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingRequest) {
            return back()->with(
                'error',
                'You already have a pending room-change request.'
            );
        }

        if (
            (int) $validated['requested_bed_id'] ===
            (int) $currentStay->bed_id
        ) {
            return back()->withErrors([
                'requested_bed_id' =>
                    'The selected bed is already assigned to you.',
            ]);
        }

        $room = Room::query()
            ->with('beds')
            ->findOrFail(
                $validated['requested_room_id']
            );

        if (
            (int) $room->building_id !==
                (int) $validated['requested_building_id']
            || (int) $room->floor_id !==
                (int) $validated['requested_floor_id']
        ) {
            return back()->withErrors([
                'requested_room_id' =>
                    'The selected room does not belong to the selected building and floor.',
            ]);
        }

        $bed = $room->beds->firstWhere(
            'id',
            (int) $validated['requested_bed_id']
        );

        if (!$bed || $bed->status !== 'vacant') {
            return back()->withErrors([
                'requested_bed_id' =>
                    'The selected bed is no longer vacant.',
            ]);
        }

        $changeRequest = DB::transaction(
            function () use (
                $resident,
                $currentStay,
                $validated
            ): RoomChangeRequest {
                return RoomChangeRequest::create([
                    'resident_id' => $resident->id,

                    'current_stay_id' =>
                        $currentStay->id,

                    'reason' =>
                        trim($validated['reason']),

                    'requested_building_id' =>
                        $validated['requested_building_id'],

                    'requested_floor_id' =>
                        $validated['requested_floor_id'],

                    'requested_room_id' =>
                        $validated['requested_room_id'],

                    'requested_bed_id' =>
                        $validated['requested_bed_id'],

                    'status' => 'pending',

                    'request_source' =>
                        'resident_portal',

                    'requested_by' => null,

                    'requested_by_resident_id' =>
                        $resident->id,
                ]);
            }
        );

        return redirect()
            ->route(
                'resident.room-change-requests.show',
                [
                    'roomChangeRequest' =>
                        $changeRequest->id,
                ]
            )
            ->with(
                'success',
                'Room-change request submitted successfully.'
            );
    }

    public function show(
        RoomChangeRequest $roomChangeRequest
    ): Response {
        $roomChangeRequest =
            $this->residentRequestOrFail(
                $roomChangeRequest
            );

        $roomChangeRequest->load([
            'currentStay.building:id,name',
            'currentStay.floor:id,name',
            'currentStay.room:id,room_number',
            'currentStay.bed:id,bed_number',

            'requestedBuilding:id,name',
            'requestedFloor:id,name',
            'requestedRoom:id,room_number,monthly_rent_per_bed',
            'requestedBed:id,bed_number,status',

            'newStay.building:id,name',
            'newStay.floor:id,name',
            'newStay.room:id,room_number',
            'newStay.bed:id,bed_number',

            'reviewedBy:id,name',
        ]);

        return Inertia::render(
            'ResidentPortal/RoomChangeRequests/Show',
            [
                'request' =>
                    $this->transformRequest(
                        $roomChangeRequest
                    ),
            ]
        );
    }

    public function cancel(
        RoomChangeRequest $roomChangeRequest
    ): RedirectResponse {
        $roomChangeRequest =
            $this->residentRequestOrFail(
                $roomChangeRequest
            );

        if (!$roomChangeRequest->can_cancel) {
            return back()->with(
                'error',
                'Only a pending room-change request can be cancelled.'
            );
        }

        $roomChangeRequest->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return redirect()
            ->route(
                'resident.room-change-requests.index'
            )
            ->with(
                'success',
                'Room-change request cancelled successfully.'
            );
    }

    protected function residentRequestOrFail(
        RoomChangeRequest $changeRequest
    ): RoomChangeRequest {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        abort_unless(
            (int) $changeRequest->resident_id ===
                (int) $resident->id,
            403
        );

        return $changeRequest;
    }

    protected function transformRequest(
        RoomChangeRequest $changeRequest
    ): array {
        return [
            'id' => $changeRequest->id,

            'reason' => $changeRequest->reason,

            'status' => $changeRequest->status,

            'status_label' =>
                $changeRequest->status_label,

            'request_source' =>
                $changeRequest->request_source,

            'can_cancel' =>
                $changeRequest->can_cancel,

            'current_stay' =>
                $changeRequest->currentStay
                    ? [
                        'id' =>
                            $changeRequest->currentStay->id,

                        'building_name' =>
                            $changeRequest->currentStay
                                ->building?->name,

                        'floor_name' =>
                            $changeRequest->currentStay
                                ->floor?->name,

                        'room_number' =>
                            $changeRequest->currentStay
                                ->room?->room_number,

                        'bed_number' =>
                            $changeRequest->currentStay
                                ->bed?->bed_number,

                        'billing_basis' =>
                            $changeRequest->currentStay
                                ->billing_basis,

                        'rent_amount' =>
                            (float) $changeRequest
                                ->currentStay
                                ->rent_amount,

                        'daily_rate' =>
                            $changeRequest->currentStay
                                ->daily_rate !== null
                                ? (float) $changeRequest
                                    ->currentStay
                                    ->daily_rate
                                : null,
                    ]
                    : null,

            'requested_room' => [
                'building_name' =>
                    $changeRequest
                        ->requestedBuilding?->name,

                'floor_name' =>
                    $changeRequest
                        ->requestedFloor?->name,

                'room_number' =>
                    $changeRequest
                        ->requestedRoom?->room_number,

                'bed_number' =>
                    $changeRequest
                        ->requestedBed?->bed_number,

                'bed_status' =>
                    $changeRequest
                        ->requestedBed?->status,

                'monthly_rent_per_bed' =>
                    $changeRequest->requestedRoom
                        ? (float) $changeRequest
                            ->requestedRoom
                            ->monthly_rent_per_bed
                        : null,
            ],

            'effective_from' =>
                $changeRequest->effective_from,

            'new_billing_basis' =>
                $changeRequest->new_billing_basis,

            'new_rent_amount' =>
                $changeRequest->new_rent_amount !== null
                    ? (float) $changeRequest
                        ->new_rent_amount
                    : null,

            'new_daily_rate' =>
                $changeRequest->new_daily_rate !== null
                    ? (float) $changeRequest
                        ->new_daily_rate
                    : null,

            'new_expected_check_out_date' =>
                $changeRequest
                    ->new_expected_check_out_date,

            'new_stay' =>
                $changeRequest->newStay
                    ? [
                        'id' =>
                            $changeRequest->newStay->id,

                        'building_name' =>
                            $changeRequest->newStay
                                ->building?->name,

                        'floor_name' =>
                            $changeRequest->newStay
                                ->floor?->name,

                        'room_number' =>
                            $changeRequest->newStay
                                ->room?->room_number,

                        'bed_number' =>
                            $changeRequest->newStay
                                ->bed?->bed_number,
                    ]
                    : null,

            'admin_notes' =>
                $changeRequest->admin_notes,

            'reviewed_by' =>
                $changeRequest->reviewedBy?->name,

            'reviewed_at' =>
                $changeRequest->reviewed_at,

            'cancelled_at' =>
                $changeRequest->cancelled_at,

            'created_at' =>
                $changeRequest->created_at,

            'updated_at' =>
                $changeRequest->updated_at,
        ];
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\CheckoutRequest;
use App\Models\Floor;
use App\Models\Inventory;
use App\Models\Resident;
use App\Models\ResidentStay;
use App\Models\Room;
use App\Services\RoomAllotmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckInOutController extends Controller
{
    public function index(Request $request): Response
    {
        $awaitingCheckIn = ResidentStay::query()
            ->with([
                'resident',
                'building',
                'floor',
                'room',
                'bed',
            ])
            ->where('status', 'upcoming')
            ->where('check_in_status', false)
            ->orderBy('check_in_date')
            ->get();

        $checkedInStays = ResidentStay::query()
            ->with([
                'resident',
                'building',
                'floor',
                'room',
                'bed',

                'inventoryAssignments' => function ($query) {
                    $query
                        ->where('is_returned', false)
                        ->with([
                            'inventory:id,item_name,category,unit',
                        ])
                        ->orderBy('id');
                },

                'activeCheckoutRequest' => function ($query) {
                    $query->with([
                        'assignedWarden:id,name,email',
                    ]);
                },
            ])
            ->where('status', 'active')
            ->where('check_in_status', true)
            ->orderByDesc('check_in_date')
            ->get()
            ->map(function (ResidentStay $stay): array {
                $checkoutRequest =
                    $stay->activeCheckoutRequest;

                return [
                    'id' => $stay->id,

                    'resident_id' =>
                        $stay->resident_id,

                    'resident' => [
                        'id' =>
                            $stay->resident?->id,

                        'first_name' =>
                            $stay->resident?->first_name,

                        'last_name' =>
                            $stay->resident?->last_name,

                        'name' => trim(
                            ($stay->resident?->first_name ?? '')
                            . ' '
                            . ($stay->resident?->last_name ?? '')
                        ),

                        'resident_code' =>
                            $stay->resident?->resident_code,

                        'phone' =>
                            $stay->resident?->phone,

                        'photo_url' =>
                            $stay->resident?->photo_url,

                        'status' =>
                            $stay->resident?->status,
                    ],

                    'building' => [
                        'id' =>
                            $stay->building?->id,

                        'name' =>
                            $stay->building?->name,
                    ],

                    'floor' => [
                        'id' =>
                            $stay->floor?->id,

                        'name' =>
                            $stay->floor?->name
                            ?? $stay->floor?->floor_number,
                    ],

                    'room' => [
                        'id' =>
                            $stay->room?->id,

                        'room_number' =>
                            $stay->room?->room_number,
                    ],

                    'bed' => [
                        'id' =>
                            $stay->bed?->id,

                        'bed_number' =>
                            $stay->bed?->bed_number,
                    ],

                    'check_in_date' =>
                        $stay->check_in_date?->toDateString(),

                    'expected_check_out_date' =>
                        $stay->expected_check_out_date
                                ?->toDateString(),

                    'billing_basis' =>
                        $stay->billing_basis,

                    'rent_amount' =>
                        (float) ($stay->rent_amount ?? 0),

                    'daily_rate' =>
                        (float) ($stay->daily_rate ?? 0),

                    'deposit_amount' =>
                        (float) ($stay->deposit_amount ?? 0),

                    'status' =>
                        $stay->status,

                    'check_in_status' =>
                        (bool) $stay->check_in_status,

                    'inventory_assignments' =>
                        $stay->inventoryAssignments
                            ->map(function ($assignment): array {
                                return [
                                    'id' =>
                                        $assignment->id,

                                    'inventory_id' =>
                                        $assignment->inventory_id,

                                    'inventory' => [
                                        'id' =>
                                            $assignment
                                                ->inventory?->id,

                                        'item_name' =>
                                            $assignment
                                                ->inventory
                                                    ?->item_name,

                                        'category' =>
                                            $assignment
                                                ->inventory
                                                    ?->category,

                                        'unit' =>
                                            $assignment
                                                ->inventory
                                                    ?->unit,
                                    ],

                                    'quantity' =>
                                        (int) $assignment->quantity,

                                    'condition_at_issue' =>
                                        $assignment
                                            ->condition_at_issue,

                                    'issue_notes' =>
                                        $assignment->issue_notes,

                                    'assigned_at' =>
                                        $assignment->assigned_at,
                                ];
                            })
                            ->values(),

                    'checkout_request' =>
                        $checkoutRequest
                        ? [
                            'id' =>
                                $checkoutRequest->id,

                            'status' =>
                                $checkoutRequest->status,

                            'requested_checkout_date' =>
                                $checkoutRequest
                                    ->requested_checkout_date
                                        ?->toDateString(),

                            'requested_at' =>
                                $checkoutRequest
                                    ->requested_at,

                            'requested_by_type' =>
                                $checkoutRequest
                                    ->requested_by_type,

                            'is_short_notice' =>
                                (bool) $checkoutRequest
                                    ->is_short_notice,

                            'actual_notice_days' =>
                                (int) $checkoutRequest
                                    ->actual_notice_days,

                            'required_notice_days' =>
                                (int) $checkoutRequest
                                    ->required_notice_days,

                            'admin_review_status' =>
                                $checkoutRequest
                                    ->admin_review_status,

                            'warden_review_status' =>
                                $checkoutRequest
                                    ->warden_review_status,

                            'assigned_warden' =>
                                $checkoutRequest
                                    ->assignedWarden
                                ? [
                                    'id' =>
                                        $checkoutRequest
                                            ->assignedWarden
                                            ->id,

                                    'name' =>
                                        $checkoutRequest
                                            ->assignedWarden
                                            ->name,

                                    'email' =>
                                        $checkoutRequest
                                            ->assignedWarden
                                            ->email,
                                ]
                                : null,
                        ]
                        : null,
                ];
            })
            ->values();

        $unassigned = Resident::query()
            ->whereDoesntHave(
                'stays',
                function ($query): void {
                    $query->whereIn('status', [
                        'upcoming',
                        'active',
                    ]);
                }
            )
            ->whereIn('status', [
                'upcoming',
                'active',
            ])
            ->orderBy('first_name')
            ->get([
                'id',
                'first_name',
                'last_name',
                'resident_code',
                'phone',
                'photo_url',
                'status',
            ]);

        $studentInventory = Inventory::query()
            ->where('category', 'student')
            ->where('available', '>', 0)
            ->orderBy('item_name')
            ->get([
                'id',
                'item_name',
                'available',
                'unit',
            ]);

        return Inertia::render(
            'CheckInOut/Index',
            [
                'awaitingCheckIn' =>
                    $awaitingCheckIn,

                'checkedInStays' =>
                    $checkedInStays,

                'unassignedResidents' =>
                    $unassigned,

                'studentInventory' =>
                    $studentInventory,

                'buildings' =>
                    Building::query()
                        ->orderBy('name')
                        ->get([
                            'id',
                            'name',
                        ]),

                'floors' =>
                    Floor::query()
                        ->orderBy('floor_number')
                        ->get([
                            'id',
                            'name',
                            'building_id',
                        ]),

                'rooms' =>
                    Room::query()
                        ->with('beds')
                        ->orderBy('room_number')
                        ->get([
                            'id',
                            'room_number',
                            'building_id',
                            'floor_id',
                            'capacity',
                            'occupied_beds',
                            'monthly_rent_per_bed',
                        ]),

                'checkoutPolicy' => [
                    'required_notice_days' => 30,

                    'today' =>
                        today()->toDateString(),

                    'minimum_recommended_date' =>
                        today()
                            ->addDays(30)
                            ->toDateString(),

                    'short_notice_message' =>
                        'The selected checkout date provides less than 30 days notice. Additional charges may apply according to hostel policy and terms and conditions.',
                ],

                'checkoutRequestStatuses' => [
                    CheckoutRequest::STATUS_PENDING,
                    CheckoutRequest::STATUS_UNDER_ADMIN_REVIEW,
                    CheckoutRequest::STATUS_ASSIGNED_TO_WARDEN,
                    CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS,
                    CheckoutRequest::STATUS_WARDEN_APPROVED,
                    CheckoutRequest::STATUS_ON_HOLD,
                    CheckoutRequest::STATUS_ADMIN_APPROVED,
                    CheckoutRequest::STATUS_READY_FOR_EXIT,
                ],
            ]
        );
    }

    public function allotRoom(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'resident_id' => [
                'required',
                'exists:residents,id',
            ],

            'building_id' => [
                'required',
                'exists:buildings,id',
            ],

            'floor_id' => [
                'required',
                'exists:floors,id',
            ],

            'room_id' => [
                'required',
                'exists:rooms,id',
            ],

            'bed_id' => [
                'required',
                'exists:beds,id',
            ],

            'check_in_date' => [
                'required',
                'date',
            ],

            'expected_check_out_date' => [
                'nullable',
                'date',
                'after_or_equal:check_in_date',
            ],

            'rent_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'deposit_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'bill_type' => [
                'nullable',
                'in:monthly,session,daily',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'billing_basis' => [
                'required',
                'in:monthly,daily',
            ],

            'daily_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'required_if:billing_basis,daily',
            ],
        ]);

        $resident = Resident::findOrFail(
            $validated['resident_id']
        );

        try {
            RoomAllotmentService::allot(
                $resident,
                $validated
            );
        } catch (\RuntimeException $exception) {
            return back()->with(
                'error',
                $exception->getMessage()
            );
        }

        return back()->with(
            'success',
            "{$resident->first_name}'s room was allotted. Actual check-in is still pending."
        );
    }

    public function confirmCheckin(
        Request $request,
        ResidentStay $stay
    ): RedirectResponse {
        $validated = $request->validate([
            'check_in_date' => [
                'required',
                'date',
            ],

            'inventory' => [
                'nullable',
                'array',
            ],

            'inventory.*.inventory_id' => [
                'required',
                'exists:inventory,id',
            ],

            'inventory.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'inventory.*.condition_at_issue' => [
                'nullable',
                'in:new,good,fair,damaged',
            ],

            'inventory.*.notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        try {
            RoomAllotmentService::confirmCheckIn(
                $stay,
                $validated
            );

            /*
            * Enable resident portal access after successful check-in.
            */
            $resident = Resident::find($stay->resident_id);

            if ($resident) {
                $updates = [
                    'portal_enabled' => true,
                    'must_change_password' => true,
                ];

                /*
                * Only create/reset the initial password if the resident
                * does not already have portal credentials.
                */
                if (
                    empty($resident->password)
                    && !empty($resident->date_of_birth)
                ) {
                    $updates['password'] = Hash::make(
                        $resident->date_of_birth
                    );
                }

                $resident->update($updates);
            }
        } catch (
            \RuntimeException |
            ValidationException $exception
        ) {
            if (
                $exception instanceof
                ValidationException
            ) {
                throw $exception;
            }

            return back()->with(
                'error',
                $exception->getMessage()
            );
        }

        return back()->with(
            'success',
            "{$stay->resident->first_name} checked in successfully."
        );
    }

    /**
     * Legacy direct-checkout endpoint.
     *
     * Keep this method temporarily, but it is no longer called
     * from the normal Check-In / Check-Out page.
     */
    public function reviewCheckout(
        Request $request,
        ResidentStay $stay
    ): RedirectResponse {
        return back()->with(
            'error',
            'Direct checkout has been disabled. Please create or continue the resident checkout request workflow.'
        );
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\CheckoutRequest;
use App\Services\CheckoutInspectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WardenCheckoutRequestController extends Controller
{
    public function __construct(
        protected CheckoutInspectionService
        $inspectionService
    ) {
    }

    public function index(
        Request $request
    ): Response {
        $user = $request->user();

        abort_unless($user, 401);

        $query =
            CheckoutRequest::query()
                ->where(
                    'assigned_warden_id',
                    $user->id
                )
                ->with([
                    'resident:id,resident_code,first_name,last_name,phone,photo_url',

                    'stay:id,resident_id,building_id,floor_id,room_id,bed_id,check_in_date,status',

                    'stay.building:id,name',
                    'stay.floor:id,name,floor_number',
                    'stay.room:id,room_number',
                    'stay.bed:id,bed_number',
                ]);

        if (
            $status =
            $request->string(
                'status'
            )->toString()
        ) {
            $query->where(
                'status',
                $status
            );
        }

        if (
            $search =
            trim(
                $request->string(
                    'search'
                )->toString()
            )
        ) {
            $query->whereHas(
                'resident',
                function (Builder $residentQuery) use ($search): void {
                    $residentQuery
                        ->where(
                            'first_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'last_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'resident_code',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'phone',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        $requests =
            $query
                ->latest('warden_assigned_at')
                ->paginate(20)
                ->withQueryString();

        $requests
            ->getCollection()
            ->transform(
                fn(
                CheckoutRequest $checkoutRequest
            ) => $this->transformListItem(
                    $checkoutRequest
                )
            );

        return Inertia::render(
            'CheckInOut/WardenInspections/Index',
            [
                'requests' =>
                    $requests,

                'filters' =>
                    $request->only([
                        'search',
                        'status',
                    ]),

                'stats' => [
                    'pending' =>
                        CheckoutRequest::query()
                            ->where(
                                'assigned_warden_id',
                                $user->id
                            )
                            ->where(
                                'status',
                                CheckoutRequest::STATUS_ASSIGNED_TO_WARDEN
                            )
                            ->count(),

                    'in_progress' =>
                        CheckoutRequest::query()
                            ->where(
                                'assigned_warden_id',
                                $user->id
                            )
                            ->where(
                                'status',
                                CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS
                            )
                            ->count(),

                    'approved' =>
                        CheckoutRequest::query()
                            ->where(
                                'assigned_warden_id',
                                $user->id
                            )
                            ->where(
                                'warden_review_status',
                                'approved'
                            )
                            ->count(),

                    'on_hold' =>
                        CheckoutRequest::query()
                            ->where(
                                'assigned_warden_id',
                                $user->id
                            )
                            ->where(
                                'warden_review_status',
                                'hold'
                            )
                            ->count(),
                ],
            ]
        );
    }

    public function show(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): Response {
        $user = $request->user();

        abort_unless($user, 401);

        $this->authorizeAssignedWarden(
            $checkoutRequest,
            $user->id
        );

        if (
            $checkoutRequest->status ===
            CheckoutRequest::STATUS_ASSIGNED_TO_WARDEN
        ) {
            $this->inspectionService
                ->initialize(
                    $checkoutRequest,
                    $user
                );

            $checkoutRequest->refresh();
        }

        $checkoutRequest->load([
            'resident:id,resident_code,first_name,last_name,phone,email,photo_url',

            'stay:id,resident_id,building_id,floor_id,room_id,bed_id,check_in_date,expected_check_out_date,billing_basis,rent_amount,daily_rate,deposit_amount,status',

            'stay.building:id,name',
            'stay.floor:id,name,floor_number',
            'stay.room:id,room_number',
            'stay.bed:id,bed_number',

            'inventoryReviews' =>
                function ($query) {
                    $query
                        ->with([
                            'inventory:id,item_name,category,unit',

                            'assignment:id,inventory_id,quantity,condition_at_issue,issue_notes,assigned_at',
                        ])
                        ->orderBy('id');
                },

            'histories' =>
                fn($query) =>
                $query
                    ->oldest('created_at')
                    ->oldest('id'),
        ]);

        return Inertia::render(
            'CheckInOut/WardenInspections/Show',
            [
                'checkoutRequest' =>
                    $this->transformDetails(
                        $checkoutRequest
                    ),
            ]
        );
    }

    public function start(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user, 401);

        $this->inspectionService
            ->initialize(
                $checkoutRequest,
                $user
            );

        return redirect()
            ->route(
                'warden-checkout-inspections.show',
                $checkoutRequest
            )
            ->with(
                'success',
                'Checkout inspection started.'
            );
    }

    public function save(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $validated =
            $this->validateInspection(
                $request
            );

        $this->inspectionService
            ->saveDraft(
                $checkoutRequest,
                $request->user(),
                $validated
            );

        return back()->with(
            'success',
            'Inspection draft saved.'
        );
    }

    public function approve(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $validated =
            $this->validateInspection(
                $request
            );

        $this->inspectionService
            ->approve(
                $checkoutRequest,
                $request->user(),
                $validated
            );

        return redirect()
            ->route(
                'warden-checkout-inspections.index'
            )
            ->with(
                'success',
                'Inspection approved and sent for final administration review.'
            );
    }

    public function hold(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $validated =
            $request->validate([
                'warden_review_notes' => [
                    'required',
                    'string',
                    'max:3000',
                ],
            ]);

        $this->inspectionService
            ->hold(
                $checkoutRequest,
                $request->user(),
                $validated[
                    'warden_review_notes'
                ]
            );

        return redirect()
            ->route(
                'warden-checkout-inspections.index'
            )
            ->with(
                'success',
                'Inspection placed on hold.'
            );
    }

    public function reject(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $validated =
            $request->validate([
                'warden_review_notes' => [
                    'required',
                    'string',
                    'max:3000',
                ],
            ]);

        $this->inspectionService
            ->reject(
                $checkoutRequest,
                $request->user(),
                $validated[
                    'warden_review_notes'
                ]
            );

        return redirect()
            ->route(
                'warden-checkout-inspections.index'
            )
            ->with(
                'success',
                'Inspection rejected.'
            );
    }

    protected function validateInspection(
        Request $request
    ): array {
        return $request->validate([
            'warden_review_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'inventory_reviews' => [
                'nullable',
                'array',
            ],

            'inventory_reviews.*.id' => [
                'required',
                'integer',
                'exists:checkout_inventory_reviews,id',
            ],

            'inventory_reviews.*.returned_good_quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'inventory_reviews.*.returned_damaged_quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'inventory_reviews.*.missing_quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'inventory_reviews.*.condition_at_review' => [
                'nullable',
                Rule::in([
                    'new',
                    'good',
                    'fair',
                    'damaged',
                    'missing',
                ]),
            ],

            'inventory_reviews.*.damage_charge' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'inventory_reviews.*.review_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);
    }

    protected function authorizeAssignedWarden(
        CheckoutRequest $checkoutRequest,
        int $userId
    ): void {
        abort_unless(
            (int) $checkoutRequest
                ->assigned_warden_id
            === $userId,
            403
        );
    }

    protected function transformListItem(
        CheckoutRequest $request
    ): array {
        return [
            'id' => $request->id,

            'resident' => [
                'name' => trim(
                    ($request->resident
                            ?->first_name ?? '')
                    . ' '
                    . ($request->resident
                            ?->last_name ?? '')
                ),

                'resident_code' =>
                    $request->resident
                            ?->resident_code,

                'phone' =>
                    $request->resident?->phone,

                'photo_url' =>
                    $request->resident
                            ?->photo_url,
            ],

            'stay' => [
                'building' =>
                    $request->stay
                        ?->building?->name,

                'floor' =>
                    $request->stay
                        ?->floor?->name
                    ?? $request->stay
                        ?->floor
                            ?->floor_number,

                'room' =>
                    $request->stay
                        ?->room
                            ?->room_number,

                'bed' =>
                    $request->stay
                        ?->bed?->bed_number,
            ],

            'status' =>
                $request->status,

            'warden_review_status' =>
                $request
                    ->warden_review_status,

            'requested_checkout_date' =>
                $request
                    ->requested_checkout_date
                        ?->toDateString(),

            'requested_at' =>
                $request->requested_at,

            'warden_assigned_at' =>
                $request
                    ->warden_assigned_at,

            'is_short_notice' =>
                (bool) $request
                    ->is_short_notice,

            'actual_notice_days' =>
                $request
                    ->actual_notice_days,

            'outstanding_amount_at_request' =>
                (float) $request
                    ->outstanding_amount_at_request,
        ];
    }

    protected function transformDetails(
        CheckoutRequest $request
    ): array {
        return [
            'id' => $request->id,

            'status' =>
                $request->status,

            'requested_checkout_date' =>
                $request
                    ->requested_checkout_date
                        ?->toDateString(),

            'requested_at' =>
                $request->requested_at,

            'reason' =>
                $request->reason,

            'resident_notes' =>
                $request->resident_notes,

            'is_short_notice' =>
                (bool) $request
                    ->is_short_notice,

            'required_notice_days' =>
                $request
                    ->required_notice_days,

            'actual_notice_days' =>
                $request
                    ->actual_notice_days,

            'outstanding_amount_at_request' =>
                (float) $request
                    ->outstanding_amount_at_request,

            'short_notice_charge' =>
                (float) $request
                    ->short_notice_charge,

            'asset_damage_charge' =>
                (float) $request
                    ->asset_damage_charge,

            'warden_review_status' =>
                $request
                    ->warden_review_status,

            'warden_review_notes' =>
                $request
                    ->warden_review_notes,

            'resident' => [
                'id' =>
                    $request->resident?->id,

                'name' => trim(
                    ($request->resident
                            ?->first_name ?? '')
                    . ' '
                    . ($request->resident
                            ?->last_name ?? '')
                ),

                'resident_code' =>
                    $request->resident
                            ?->resident_code,

                'phone' =>
                    $request->resident?->phone,

                'email' =>
                    $request->resident?->email,

                'photo_url' =>
                    $request->resident
                            ?->photo_url,
            ],

            'stay' => [
                'building' =>
                    $request->stay
                        ?->building?->name,

                'floor' =>
                    $request->stay
                        ?->floor?->name
                    ?? $request->stay
                        ?->floor
                            ?->floor_number,

                'room' =>
                    $request->stay
                        ?->room
                            ?->room_number,

                'bed' =>
                    $request->stay
                        ?->bed?->bed_number,

                'check_in_date' =>
                    $request->stay
                        ?->check_in_date
                            ?->toDateString(),

                'expected_check_out_date' =>
                    $request->stay
                        ?->expected_check_out_date
                            ?->toDateString(),

                'billing_basis' =>
                    $request->stay
                            ?->billing_basis,

                'rent_amount' =>
                    (float) (
                        $request->stay
                                ?->rent_amount ?? 0
                    ),

                'daily_rate' =>
                    (float) (
                        $request->stay
                                ?->daily_rate ?? 0
                    ),

                'deposit_amount' =>
                    (float) (
                        $request->stay
                                ?->deposit_amount ?? 0
                    ),
            ],

            'inventory_reviews' =>
                $request
                    ->inventoryReviews
                    ->map(
                        fn($review) => [
                            'id' =>
                                $review->id,

                            'assignment_id' =>
                                $review
                                    ->resident_inventory_assignment_id,

                            'item_name' =>
                                $review
                                    ->inventory
                                        ?->item_name,

                            'category' =>
                                $review
                                    ->inventory
                                        ?->category,

                            'unit' =>
                                $review
                                    ->inventory
                                        ?->unit
                                ?? 'pieces',

                            'assigned_quantity' =>
                                (int) $review
                                    ->assigned_quantity,

                            'condition_at_issue' =>
                                $review
                                    ->assignment
                                        ?->condition_at_issue,

                            'issue_notes' =>
                                $review
                                    ->assignment
                                        ?->issue_notes,

                            'returned_good_quantity' =>
                                (int) $review
                                    ->returned_good_quantity,

                            'returned_damaged_quantity' =>
                                (int) $review
                                    ->returned_damaged_quantity,

                            'missing_quantity' =>
                                (int) $review
                                    ->missing_quantity,

                            'condition_at_review' =>
                                $review
                                    ->condition_at_review,

                            'damage_charge' =>
                                (float) $review
                                    ->damage_charge,

                            'review_notes' =>
                                $review
                                    ->review_notes,
                        ]
                    )
                    ->values(),

            'histories' =>
                $request
                    ->histories
                    ->map(
                        fn($history) => [
                            'id' =>
                                $history->id,

                            'action' =>
                                $history->action,

                            'notes' =>
                                $history->notes,

                            'created_at' =>
                                $history
                                    ->created_at,
                        ]
                    )
                    ->values(),
        ];
    }
}
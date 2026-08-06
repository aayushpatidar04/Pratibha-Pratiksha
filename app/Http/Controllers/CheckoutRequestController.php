<?php

namespace App\Http\Controllers;

use App\Models\CheckoutInventoryReview;
use App\Models\CheckoutRequest;
use App\Models\FeeInvoice;
use App\Models\Resident;
use App\Models\ResidentStay;
use App\Models\User;
use App\Services\CheckoutApprovalService;
use App\Services\CheckoutRequestHistoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutRequestController extends Controller
{
    public function __construct(
        protected CheckoutApprovalService
        $checkoutApprovalService
    ) {
    }

    public function index(
        Request $request
    ): Response {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'string',
                'max:100',
            ],

            'building_id' => [
                'nullable',
                'integer',
                'exists:buildings,id',
            ],

            'assigned_warden_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ]);

        $query = CheckoutRequest::query()
            ->with([
                'resident:id,resident_code,first_name,last_name,phone,photo_url,status',

                'stay:id,resident_id,building_id,floor_id,room_id,bed_id,check_in_date,expected_check_out_date,status',

                'stay.building:id,name',
                'stay.floor:id,name,floor_number',
                'stay.room:id,room_number',
                'stay.bed:id,bed_number',

                'assignedWarden:id,name,email',

                'histories' => function ($query) {
                    $query
                        ->latest('created_at')
                        ->latest('id');
                },
            ]);

        if (
            filled(
                $validated['search'] ?? null
            )
        ) {
            $search = trim(
                $validated['search']
            );

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->whereHas(
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
                    )
                    ->orWhere(
                        'reason',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if (
            filled(
                $validated['status'] ?? null
            )
        ) {
            $query->where(
                'status',
                $validated['status']
            );
        }

        if (
            filled(
                $validated['building_id'] ?? null
            )
        ) {
            $query->whereHas(
                'stay',
                fn(Builder $stayQuery) =>
                $stayQuery->where(
                    'building_id',
                    $validated['building_id']
                )
            );
        }

        if (
            filled(
                $validated['assigned_warden_id']
                ?? null
            )
        ) {
            $query->where(
                'assigned_warden_id',
                $validated[
                    'assigned_warden_id'
                ]
            );
        }

        $requests = $query
            ->latest('requested_at')
            ->paginate(20)
            ->withQueryString();

        $requests
            ->getCollection()
            ->transform(
                fn(
                CheckoutRequest $checkoutRequest
            ) => $this->transformRequest(
                    $checkoutRequest
                )
            );

        return Inertia::render(
            'CheckInOut/CheckoutRequests/Index',
            [
                'requests' =>
                    $requests,

                'stats' =>
                    $this->stats(),

                'filters' => [
                    'search' =>
                        $validated['search']
                        ?? '',

                    'status' =>
                        $validated['status']
                        ?? '',

                    'building_id' =>
                        $validated['building_id']
                        ?? null,

                    'assigned_warden_id' =>
                        $validated[
                            'assigned_warden_id'
                        ] ?? null,
                ],

                'statuses' =>
                    $this->statusOptions(),

                'eligibleResidents' =>
                    $this->eligibleResidents(),

                'wardens' =>
                    $this->wardenOptions(),

                'buildings' =>
                    \App\Models\Building::query()
                        ->orderBy('name')
                        ->get([
                            'id',
                            'name',
                        ]),

                'policy' => [
                    'required_notice_days' =>
                        30,

                    'today' =>
                        today()
                            ->toDateString(),

                    'minimum_recommended_date' =>
                        today()
                            ->addDays(30)
                            ->toDateString(),

                    'short_notice_message' =>
                        'The selected checkout date provides less than 30 days notice. Additional charges may apply as per hostel policy and terms and conditions.',
                ],
            ]
        );
    }

    /**
     * Admin creates a request on behalf of resident.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'resident_id' => [
                'required',
                'integer',
                'exists:residents,id',
            ],

            'resident_stay_id' => [
                'required',
                'integer',
                'exists:resident_stays,id',
            ],

            'requested_checkout_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'reason' => [
                'required',
                'string',
                'max:3000',
            ],

            'resident_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'short_notice_warning_accepted' => [
                'nullable',
                'boolean',
            ],
        ]);

        $resident = Resident::query()
            ->findOrFail(
                $validated['resident_id']
            );

        $stay = ResidentStay::query()
            ->whereKey(
                $validated['resident_stay_id']
            )
            ->where(
                'resident_id',
                $resident->id
            )
            ->where('status', 'active')
            ->where(
                'check_in_status',
                true
            )
            ->first();

        if (!$stay) {
            throw ValidationException::withMessages([
                'resident_stay_id' =>
                    'The selected resident does not have this active checked-in stay.',
            ]);
        }

        $existingRequest =
            CheckoutRequest::query()
                ->where(
                    'resident_stay_id',
                    $stay->id
                )
                ->active()
                ->exists();

        if ($existingRequest) {
            return back()->with(
                'error',
                'An active checkout request already exists for this resident stay.'
            );
        }

        $notice = $this->calculateNotice(
            $validated[
                'requested_checkout_date'
            ]
        );

        $warningAccepted =
            $request->boolean(
                'short_notice_warning_accepted'
            );

        if (
            $notice['is_short_notice']
            && !$warningAccepted
        ) {
            throw ValidationException::withMessages([
                'short_notice_warning_accepted' =>
                    'Confirm the short-notice policy acknowledgement before creating the request.',
            ]);
        }

        $outstandingAmount =
            $this->calculateOutstandingAmount(
                $resident->id
            );

        DB::transaction(function () use ($request, $resident, $stay, $validated, $notice, $warningAccepted, $outstandingAmount): void {
            $checkoutRequest =
                CheckoutRequest::create([
                    'resident_id' =>
                        $resident->id,

                    'resident_stay_id' =>
                        $stay->id,

                    'requested_by_type' =>
                        'user',

                    'requested_by_id' =>
                        $request->user()?->id,

                    'requested_checkout_date' =>
                        $notice[
                            'requested_date'
                        ],

                    'requested_at' =>
                        now(),

                    'required_notice_days' =>
                        $notice[
                            'required_notice_days'
                        ],

                    'actual_notice_days' =>
                        $notice[
                            'actual_notice_days'
                        ],

                    'is_short_notice' =>
                        $notice[
                            'is_short_notice'
                        ],

                    'short_notice_warning_accepted' =>
                        $notice[
                            'is_short_notice'
                        ]
                        && $warningAccepted,

                    'warning_accepted_at' =>
                        $notice[
                            'is_short_notice'
                        ]
                        && $warningAccepted
                        ? now()
                        : null,

                    'reason' =>
                        trim(
                            $validated['reason']
                        ),

                    'resident_notes' =>
                        filled(
                            $validated[
                                'resident_notes'
                            ] ?? null
                        )
                        ? trim(
                            $validated[
                                'resident_notes'
                            ]
                        )
                        : null,

                    'status' =>
                        CheckoutRequest::STATUS_PENDING,

                    'admin_review_status' =>
                        'pending',

                    'warden_review_status' =>
                        'not_assigned',

                    'dues_clearance_status' =>
                        $outstandingAmount > 0
                        ? 'dues_pending'
                        : 'clear',

                    'outstanding_amount_at_request' =>
                        $outstandingAmount,
                ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                $checkoutRequest,

                action:
                'request_created',

                fromStatus:
                null,

                toStatus:
                CheckoutRequest::STATUS_PENDING,

                actor:
                $request->user(),

                notes:
                'Checkout request created by administration on behalf of the resident.',

                metadata: [
                    'resident_id' =>
                        $resident->id,

                    'requested_checkout_date' =>
                        $notice[
                            'requested_date'
                        ],

                    'actual_notice_days' =>
                        $notice[
                            'actual_notice_days'
                        ],

                    'is_short_notice' =>
                        $notice[
                            'is_short_notice'
                        ],
                ]
            );

            if (
                $notice['is_short_notice']
                && $warningAccepted
            ) {
                CheckoutRequestHistoryService::record(
                    checkoutRequest:
                    $checkoutRequest,

                    action:
                    'warning_accepted',

                    fromStatus:
                    CheckoutRequest::STATUS_PENDING,

                    toStatus:
                    CheckoutRequest::STATUS_PENDING,

                    actor:
                    $request->user(),

                    notes:
                    'Administration confirmed the short-notice policy while creating the request on behalf of the resident.'
                );
            }
        });

        return back()->with(
            'success',
            'Checkout request created successfully.'
        );
    }

    public function show(
        CheckoutRequest $checkoutRequest
    ): Response {
        $checkoutRequest->load([
            'resident:id,resident_code,first_name,last_name,phone,email,photo_url,status',

            'stay:id,resident_id,building_id,floor_id,room_id,bed_id,check_in_date,expected_check_out_date,billing_basis,rent_amount,daily_rate,deposit_amount,status',

            'stay.building:id,name',
            'stay.floor:id,name,floor_number',
            'stay.room:id,room_number',
            'stay.bed:id,bed_number',

            'assignedWarden:id,name,email',

            'adminReviewer:id,name,email',

            'finalApprover:id,name,email',

            'inventoryReviews' => function ($query) {
                $query
                    ->with([
                        'inventory:id,item_name,category,unit',

                        'assignment:id,inventory_id,quantity,condition_at_issue,issue_notes,assigned_at',
                    ])
                    ->orderBy('id');
            },

            'histories' => fn($query) =>
                $query
                    ->oldest('created_at')
                    ->oldest('id'),
        ]);

        return Inertia::render(
            'CheckInOut/CheckoutRequests/Show',
            [
                'checkoutRequest' =>
                    $this->transformFinalReview(
                        $checkoutRequest
                    ),
            ]
        );
    }

    /**
     * Begin preliminary admin review.
     */
    public function startReview(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        if (
            $checkoutRequest->status
            !== CheckoutRequest::STATUS_PENDING
        ) {
            return back()->with(
                'error',
                'Only pending checkout requests can be moved under admin review.'
            );
        }

        DB::transaction(function () use ($request, $checkoutRequest): void {
            $fromStatus =
                $checkoutRequest->status;

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_UNDER_ADMIN_REVIEW,

                'admin_review_status' =>
                    'pending',

                'admin_reviewed_by' =>
                    $request->user()?->id,

                'admin_reviewed_at' =>
                    now(),
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                $checkoutRequest,

                action:
                'admin_review_started',

                fromStatus:
                $fromStatus,

                toStatus:
                CheckoutRequest::STATUS_UNDER_ADMIN_REVIEW,

                actor:
                $request->user(),

                notes:
                'Preliminary administration review started.'
            );
        });

        return back()->with(
            'success',
            'Checkout request moved under admin review.'
        );
    }

    /**
     * Admin approves the preliminary stage and assigns a warden.
     */
    public function assignWarden(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'assigned_warden_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'admin_review_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'short_notice_charge' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'short_notice_charge_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'dues_clearance_status' => [
                'required',
                Rule::in([
                    'pending',
                    'clear',
                    'dues_pending',
                    'waived',
                ]),
            ],
        ]);

        if (
            !in_array(
                $checkoutRequest->status,
                [
                    CheckoutRequest::STATUS_PENDING,
                    CheckoutRequest::STATUS_UNDER_ADMIN_REVIEW,
                    CheckoutRequest::STATUS_ON_HOLD,
                ],
                true
            )
        ) {
            return back()->with(
                'error',
                'This checkout request cannot be assigned to a warden in its current status.'
            );
        }

        $warden = User::query()
            ->findOrFail(
                $validated[
                    'assigned_warden_id'
                ]
            );

        DB::transaction(function () use ($request, $checkoutRequest, $validated, $warden): void {
            $fromStatus =
                $checkoutRequest->status;

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_ASSIGNED_TO_WARDEN,

                'admin_review_status' =>
                    'approved',

                'admin_reviewed_by' =>
                    $request->user()?->id,

                'admin_reviewed_at' =>
                    now(),

                'admin_review_notes' =>
                    filled(
                        $validated[
                            'admin_review_notes'
                        ] ?? null
                    )
                    ? trim(
                        $validated[
                            'admin_review_notes'
                        ]
                    )
                    : null,

                'assigned_warden_id' =>
                    $warden->id,

                'warden_assigned_by' =>
                    $request->user()?->id,

                'warden_assigned_at' =>
                    now(),

                'warden_review_status' =>
                    'pending',

                'short_notice_charge' =>
                    (float) (
                        $validated[
                            'short_notice_charge'
                        ] ?? 0
                    ),

                'short_notice_charge_notes' =>
                    filled(
                        $validated[
                            'short_notice_charge_notes'
                        ] ?? null
                    )
                    ? trim(
                        $validated[
                            'short_notice_charge_notes'
                        ]
                    )
                    : null,

                'dues_clearance_status' =>
                    $validated[
                        'dues_clearance_status'
                    ],
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                $checkoutRequest,

                action:
                'assigned_to_warden',

                fromStatus:
                $fromStatus,

                toStatus:
                CheckoutRequest::STATUS_ASSIGNED_TO_WARDEN,

                actor:
                $request->user(),

                notes:
                'Checkout request assigned to '
                . $warden->name
                . ' for room and asset inspection.',

                metadata: [
                    'assigned_warden_id' =>
                        $warden->id,

                    'assigned_warden_name' =>
                        $warden->name,

                    'short_notice_charge' =>
                        (float) (
                            $validated[
                                'short_notice_charge'
                            ] ?? 0
                        ),

                    'dues_clearance_status' =>
                        $validated[
                            'dues_clearance_status'
                        ],
                ]
            );
        });

        return back()->with(
            'success',
            "Checkout request assigned to {$warden->name}."
        );
    }

    public function hold(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'admin_review_notes' => [
                'required',
                'string',
                'max:3000',
            ],
        ]);

        if (
            in_array(
                $checkoutRequest->status,
                [
                    CheckoutRequest::STATUS_COMPLETED,
                    CheckoutRequest::STATUS_CANCELLED,
                    CheckoutRequest::STATUS_ADMIN_REJECTED,
                    CheckoutRequest::STATUS_EXPIRED,
                ],
                true
            )
        ) {
            return back()->with(
                'error',
                'This request cannot be placed on hold.'
            );
        }

        DB::transaction(function () use ($request, $checkoutRequest, $validated): void {
            $fromStatus =
                $checkoutRequest->status;

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_ON_HOLD,

                'admin_review_status' =>
                    'hold',

                'admin_reviewed_by' =>
                    $request->user()?->id,

                'admin_reviewed_at' =>
                    now(),

                'admin_review_notes' =>
                    trim(
                        $validated[
                            'admin_review_notes'
                        ]
                    ),
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                $checkoutRequest,

                action:
                'put_on_hold',

                fromStatus:
                $fromStatus,

                toStatus:
                CheckoutRequest::STATUS_ON_HOLD,

                actor:
                $request->user(),

                notes:
                trim(
                    $validated[
                        'admin_review_notes'
                    ]
                )
            );
        });

        return back()->with(
            'success',
            'Checkout request placed on hold.'
        );
    }

    public function reject(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'admin_review_notes' => [
                'required',
                'string',
                'max:3000',
            ],
        ]);

        if (
            in_array(
                $checkoutRequest->status,
                [
                    CheckoutRequest::STATUS_COMPLETED,
                    CheckoutRequest::STATUS_CANCELLED,
                    CheckoutRequest::STATUS_ADMIN_REJECTED,
                ],
                true
            )
        ) {
            return back()->with(
                'error',
                'This checkout request can no longer be rejected.'
            );
        }

        DB::transaction(function () use ($request, $checkoutRequest, $validated): void {
            $fromStatus =
                $checkoutRequest->status;

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_ADMIN_REJECTED,

                'admin_review_status' =>
                    'rejected',

                'admin_reviewed_by' =>
                    $request->user()?->id,

                'admin_reviewed_at' =>
                    now(),

                'admin_review_notes' =>
                    trim(
                        $validated[
                            'admin_review_notes'
                        ]
                    ),
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                $checkoutRequest,

                action:
                'admin_rejected',

                fromStatus:
                $fromStatus,

                toStatus:
                CheckoutRequest::STATUS_ADMIN_REJECTED,

                actor:
                $request->user(),

                notes:
                trim(
                    $validated[
                        'admin_review_notes'
                    ]
                )
            );
        });

        return back()->with(
            'success',
            'Checkout request rejected.'
        );
    }

    protected function eligibleResidents()
    {
        return ResidentStay::query()
            ->with([
                'resident:id,resident_code,first_name,last_name,phone',
                'building:id,name',
                'room:id,room_number',
                'bed:id,bed_number',
            ])
            ->where('status', 'active')
            ->where('check_in_status', true)
            ->whereDoesntHave(
                'checkoutRequests',
                fn(Builder $query) =>
                $query->active()
            )
            ->orderBy('check_in_date')
            ->get()
            ->map(
                fn(ResidentStay $stay) => [
                    'resident_id' =>
                        $stay->resident_id,

                    'resident_stay_id' =>
                        $stay->id,

                    'name' =>
                        trim(
                            $stay->resident
                                    ?->first_name
                            . ' '
                            . $stay->resident
                                    ?->last_name
                        ),

                    'resident_code' =>
                        $stay->resident
                                ?->resident_code,

                    'phone' =>
                        $stay->resident?->phone,

                    'building_name' =>
                        $stay->building?->name,

                    'room_number' =>
                        $stay->room
                                ?->room_number,

                    'bed_number' =>
                        $stay->bed
                                ?->bed_number,

                    'check_in_date' =>
                        $stay->check_in_date
                                ?->toDateString(),
                ]
            )
            ->values();
    }

    protected function wardenOptions()
    {
        return User::query()
            ->where('is_active', true)
            ->where('role', '!=', 'super_admin')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
                'permissions',
            ])
            ->filter(
                fn(User $user) =>
                $user->hasPermission(
                    'checkout_inspections',
                    'view'
                )
                &&
                $user->hasPermission(
                    'checkout_inspections',
                    'start'
                )
            )
            ->map(
                fn(User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            )
            ->values();
    }

    protected function stats(): array
    {
        return [
            'pending' =>
                CheckoutRequest::query()
                    ->whereIn(
                        'status',
                        [
                            CheckoutRequest::STATUS_PENDING,
                            CheckoutRequest::STATUS_UNDER_ADMIN_REVIEW,
                        ]
                    )
                    ->count(),

            'assigned_to_warden' =>
                CheckoutRequest::query()
                    ->whereIn(
                        'status',
                        [
                            CheckoutRequest::STATUS_ASSIGNED_TO_WARDEN,
                            CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS,
                        ]
                    )
                    ->count(),

            'warden_approved' =>
                CheckoutRequest::query()
                    ->where(
                        'status',
                        CheckoutRequest::STATUS_WARDEN_APPROVED
                    )
                    ->count(),

            'on_hold' =>
                CheckoutRequest::query()
                    ->where(
                        'status',
                        CheckoutRequest::STATUS_ON_HOLD
                    )
                    ->count(),

            'ready_for_exit' =>
                CheckoutRequest::query()
                    ->whereIn(
                        'status',
                        [
                            CheckoutRequest::STATUS_ADMIN_APPROVED,
                            CheckoutRequest::STATUS_READY_FOR_EXIT,
                        ]
                    )
                    ->count(),

            'completed' =>
                CheckoutRequest::query()
                    ->where(
                        'status',
                        CheckoutRequest::STATUS_COMPLETED
                    )
                    ->count(),

            'short_notice' =>
                CheckoutRequest::query()
                    ->where(
                        'is_short_notice',
                        true
                    )
                    ->whereNotIn(
                        'status',
                        [
                            CheckoutRequest::STATUS_CANCELLED,
                            CheckoutRequest::STATUS_COMPLETED,
                            CheckoutRequest::STATUS_ADMIN_REJECTED,
                        ]
                    )
                    ->count(),
        ];
    }

    protected function statusOptions(): array
    {
        return [
            [
                'value' => '',
                'label' => 'All Statuses',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_PENDING,
                'label' => 'Pending',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_UNDER_ADMIN_REVIEW,
                'label' => 'Under Admin Review',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_ASSIGNED_TO_WARDEN,
                'label' => 'Assigned to Warden',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS,
                'label' => 'Warden Review in Progress',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_WARDEN_APPROVED,
                'label' => 'Warden Approved',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_WARDEN_REJECTED,
                'label' => 'Warden Rejected',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_ADMIN_APPROVED,
                'label' => 'Admin Approved',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_ADMIN_REJECTED,
                'label' => 'Admin Rejected',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_ON_HOLD,
                'label' => 'On Hold',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_READY_FOR_EXIT,
                'label' => 'Ready for Exit',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_COMPLETED,
                'label' => 'Completed',
            ],
            [
                'value' =>
                    CheckoutRequest::STATUS_CANCELLED,
                'label' => 'Cancelled',
            ],
        ];
    }

    protected function calculateNotice(
        string $checkoutDate
    ): array {
        $today = today()->startOfDay();

        $requestedDate =
            Carbon::parse(
                $checkoutDate
            )->startOfDay();

        $requiredNoticeDays = 30;

        $actualNoticeDays = max(
            0,
            $today->diffInDays(
                $requestedDate,
                false
            )
        );

        return [
            'requested_date' =>
                $requestedDate
                    ->toDateString(),

            'required_notice_days' =>
                $requiredNoticeDays,

            'actual_notice_days' =>
                $actualNoticeDays,

            'is_short_notice' =>
                $requestedDate->lt(
                    $today
                        ->copy()
                        ->addDays(
                            $requiredNoticeDays
                        )
                ),
        ];
    }

    protected function calculateOutstandingAmount(
        int $residentId
    ): float {
        return round(
            FeeInvoice::query()
                ->where(
                    'resident_id',
                    $residentId
                )
                ->whereIn(
                    'status',
                    [
                        'unpaid',
                        'partial',
                        'overdue',
                    ]
                )
                ->get([
                    'amount',
                    'paid_amount',
                ])
                ->sum(
                    fn(FeeInvoice $invoice) =>
                    max(
                        0,
                        (float) $invoice->amount
                        - (float) $invoice
                            ->paid_amount
                    )
                ),
            2
        );
    }

    protected function transformRequest(
        CheckoutRequest $checkoutRequest
    ): array {
        return [
            'id' =>
                $checkoutRequest->id,

            'resident' => [
                'id' =>
                    $checkoutRequest
                        ->resident?->id,

                'name' =>
                    trim(
                        $checkoutRequest
                            ->resident
                                ?->first_name
                        . ' '
                        . $checkoutRequest
                            ->resident
                                ?->last_name
                    ),

                'resident_code' =>
                    $checkoutRequest
                        ->resident
                            ?->resident_code,

                'phone' =>
                    $checkoutRequest
                        ->resident?->phone,

                'photo_url' =>
                    $checkoutRequest
                        ->resident
                            ?->photo_url,

                'status' =>
                    $checkoutRequest
                        ->resident?->status,
            ],

            'stay' => [
                'id' =>
                    $checkoutRequest
                        ->stay?->id,

                'building' =>
                    $checkoutRequest
                        ->stay
                        ?->building
                            ?->name,

                'floor' =>
                    $checkoutRequest
                        ->stay
                        ?->floor
                            ?->name
                    ?? $checkoutRequest
                        ->stay
                        ?->floor
                            ?->floor_number,

                'room' =>
                    $checkoutRequest
                        ->stay
                        ?->room
                            ?->room_number,

                'bed' =>
                    $checkoutRequest
                        ->stay
                        ?->bed
                            ?->bed_number,

                'check_in_date' =>
                    $checkoutRequest
                        ->stay
                        ?->check_in_date
                            ?->toDateString(),

                'expected_check_out_date' =>
                    $checkoutRequest
                        ->stay
                        ?->expected_check_out_date
                            ?->toDateString(),
            ],

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

            'reason' =>
                $checkoutRequest->reason,

            'resident_notes' =>
                $checkoutRequest
                    ->resident_notes,

            'status' =>
                $checkoutRequest->status,

            'required_notice_days' =>
                $checkoutRequest
                    ->required_notice_days,

            'actual_notice_days' =>
                $checkoutRequest
                    ->actual_notice_days,

            'is_short_notice' =>
                (bool) $checkoutRequest
                    ->is_short_notice,

            'short_notice_warning_accepted' =>
                (bool) $checkoutRequest
                    ->short_notice_warning_accepted,

            'short_notice_charge' =>
                (float) $checkoutRequest
                    ->short_notice_charge,

            'short_notice_charge_notes' =>
                $checkoutRequest
                    ->short_notice_charge_notes,

            'admin_review_status' =>
                $checkoutRequest
                    ->admin_review_status,

            'admin_review_notes' =>
                $checkoutRequest
                    ->admin_review_notes,

            'admin_reviewed_at' =>
                $checkoutRequest
                    ->admin_reviewed_at,

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

            'warden_review_status' =>
                $checkoutRequest
                    ->warden_review_status,

            'dues_clearance_status' =>
                $checkoutRequest
                    ->dues_clearance_status,

            'outstanding_amount_at_request' =>
                (float) $checkoutRequest
                    ->outstanding_amount_at_request,

            'asset_damage_charge' =>
                (float) $checkoutRequest
                    ->asset_damage_charge,

            'other_checkout_charge' =>
                (float) $checkoutRequest
                    ->other_checkout_charge,

            'requested_by_label' =>
                $checkoutRequest
                    ->requested_by_type
                === 'resident'
                ? 'Resident'
                : 'Administration',

            'histories' =>
                $checkoutRequest
                    ->histories
                    ->sortBy('created_at')
                    ->values()
                    ->map(
                        fn($history) => [
                            'id' =>
                                $history->id,

                            'action' =>
                                $history->action,

                            'from_status' =>
                                $history
                                    ->from_status,

                            'to_status' =>
                                $history
                                    ->to_status,

                            'actor_type' =>
                                $history
                                    ->actor_type,

                            'notes' =>
                                $history->notes,

                            'metadata' =>
                                $history
                                    ->metadata,

                            'created_at' =>
                                $history
                                    ->created_at,
                        ]
                    ),
        ];
    }

    public function finalApprove(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'dues_clearance_status' => [
                'required',
                Rule::in([
                    'clear',
                    'waived',
                ]),
            ],

            'short_notice_charge_final' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'asset_damage_charge' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'other_checkout_charge' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'charge_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'final_approval_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        $this->checkoutApprovalService
            ->finalApprove(
                $checkoutRequest,
                $request->user(),
                $validated
            );

        return back()->with(
            'success',
            'Checkout request approved and exit authorization generated.'
        );
    }

    public function finalHold(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'final_approval_notes' => [
                'required',
                'string',
                'max:3000',
            ],
        ]);

        $this->checkoutApprovalService
            ->hold(
                $checkoutRequest,
                $request->user(),
                $validated['final_approval_notes']
            );

        return back()->with(
            'success',
            'Checkout request placed on hold.'
        );
    }

    public function finalReject(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'final_approval_notes' => [
                'required',
                'string',
                'max:3000',
            ],
        ]);

        $this->checkoutApprovalService
            ->reject(
                $checkoutRequest,
                $request->user(),
                $validated['final_approval_notes']
            );

        return redirect()
            ->route('checkout-requests.index')
            ->with(
                'success',
                'Checkout request rejected.'
            );
    }

    public function regenerateExitToken(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $this->checkoutApprovalService
            ->regenerateExitToken(
                $checkoutRequest,
                $request->user()
            );

        return back()->with(
            'success',
            'Exit authorization regenerated.'
        );
    }

    protected function transformFinalReview(
        CheckoutRequest $request
    ): array {
        return [
            'id' =>
                $request->id,

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

            'required_notice_days' =>
                (int) $request
                    ->required_notice_days,

            'actual_notice_days' =>
                (int) $request
                    ->actual_notice_days,

            'is_short_notice' =>
                (bool) $request
                    ->is_short_notice,

            'admin_review_status' =>
                $request->admin_review_status,

            'admin_review_notes' =>
                $request->admin_review_notes,

            'warden_review_status' =>
                $request->warden_review_status,

            'warden_review_notes' =>
                $request->warden_review_notes,

            'warden_reviewed_at' =>
                $request->warden_reviewed_at,

            'dues_clearance_status' =>
                $request->dues_clearance_status,

            'outstanding_amount_at_request' =>
                (float) $request
                    ->outstanding_amount_at_request,

            'short_notice_charge' =>
                (float) $request
                    ->short_notice_charge,

            'short_notice_charge_final' =>
                (float) $request
                    ->short_notice_charge_final,

            'asset_damage_charge' =>
                (float) $request
                    ->asset_damage_charge,

            'other_checkout_charge' =>
                (float) $request
                    ->other_checkout_charge,

            'charge_notes' =>
                $request->charge_notes,

            'final_approval_notes' =>
                $request->final_approval_notes,

            'final_approved_at' =>
                $request->final_approved_at,

            'exit_token' =>
                $request->exit_token,

            'exit_token_generated_at' =>
                $request
                    ->exit_token_generated_at,

            'exit_token_expires_at' =>
                $request
                    ->exit_token_expires_at,

            'gate_verified_at' =>
                $request->gate_verified_at,

            'actual_checkout_at' =>
                $request->actual_checkout_at,

            'assigned_warden' =>
                $request->assignedWarden
                ? [
                    'id' =>
                        $request
                            ->assignedWarden
                            ->id,

                    'name' =>
                        $request
                            ->assignedWarden
                            ->name,

                    'email' =>
                        $request
                            ->assignedWarden
                            ->email,
                ]
                : null,

            'final_approver' =>
                $request->finalApprover
                ? [
                    'id' =>
                        $request
                            ->finalApprover
                            ->id,

                    'name' =>
                        $request
                            ->finalApprover
                            ->name,
                ]
                : null,

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

                'status' =>
                    $request->resident?->status,
            ],

            'stay' => [
                'id' =>
                    $request->stay?->id,

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
                        ?->room?->room_number,

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
                        fn(
                        CheckoutInventoryReview $review
                    ) => [
                            'id' =>
                                $review->id,

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
                                $review->review_notes,
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

                            'from_status' =>
                                $history->from_status,

                            'to_status' =>
                                $history->to_status,

                            'actor_type' =>
                                $history->actor_type,

                            'notes' =>
                                $history->notes,

                            'metadata' =>
                                $history->metadata,

                            'created_at' =>
                                $history->created_at,
                        ]
                    )
                    ->values(),
        ];
    }


}
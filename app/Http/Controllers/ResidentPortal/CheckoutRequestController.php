<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\CheckoutRequest;
use App\Models\FeeInvoice;
use App\Models\Resident;
use App\Models\ResidentStay;
use App\Services\CheckoutRequestHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutRequestController extends Controller
{
    public function index(): Response
    {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $resident->load([
            'currentStay.building:id,name',
            'currentStay.floor:id,name,floor_number',
            'currentStay.room:id,room_number',
            'currentStay.bed:id,bed_number',
        ]);

        $currentStay = $resident->currentStay;

        $requests = CheckoutRequest::query()
            ->where('resident_id', $resident->id)
            ->with([
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
            ])
            ->latest('requested_at')
            ->latest('id')
            ->get()
            ->map(
                fn (CheckoutRequest $checkoutRequest) =>
                    $this->transformRequest(
                        $checkoutRequest
                    )
            )
            ->values();

        $activeRequest = $requests->first(
            fn (array $item) =>
                !in_array(
                    $item['status'],
                    [
                        CheckoutRequest::STATUS_COMPLETED,
                        CheckoutRequest::STATUS_CANCELLED,
                        CheckoutRequest::STATUS_ADMIN_REJECTED,
                        CheckoutRequest::STATUS_WARDEN_REJECTED,
                        CheckoutRequest::STATUS_EXPIRED,
                    ],
                    true
                )
        );

        $minimumRecommendedDate = today()
            ->addDays(30)
            ->toDateString();

        return Inertia::render(
            'ResidentPortal/CheckoutRequests/Index',
            [
                'currentStay' => $currentStay
                    ? [
                        'id' => $currentStay->id,

                        'building_name' =>
                            $currentStay->building?->name,

                        'floor_name' =>
                            $currentStay->floor?->name
                            ?? $currentStay->floor?->floor_number,

                        'room_number' =>
                            $currentStay->room?->room_number,

                        'bed_number' =>
                            $currentStay->bed?->bed_number,

                        'check_in_date' =>
                            $currentStay->check_in_date?->toDateString(),

                        'expected_check_out_date' =>
                            $currentStay
                                ->expected_check_out_date
                                ?->toDateString(),

                        'billing_basis' =>
                            $currentStay->billing_basis,

                        'status' =>
                            $currentStay->status,

                        'check_in_status' =>
                            (bool) $currentStay->check_in_status,
                    ]
                    : null,

                'requests' => $requests,

                'activeRequest' =>
                    $activeRequest,

                'policy' => [
                    'required_notice_days' => 30,

                    'today' =>
                        today()->toDateString(),

                    'minimum_recommended_date' =>
                        $minimumRecommendedDate,

                    'short_notice_message' =>
                        'Your selected checkout date provides less than 30 days notice. Additional charges may apply according to hostel policy and terms and conditions.',
                ],

                'outstandingSummary' =>
                    $this->outstandingSummary(
                        $resident
                    ),
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

        /** @var ResidentStay|null $stay */
        $stay = ResidentStay::query()
            ->where('resident_id', $resident->id)
            ->where('status', 'active')
            ->where('check_in_status', true)
            ->latest('id')
            ->first();

        if (!$stay) {
            return back()->with(
                'error',
                'You do not have an active checked-in stay.'
            );
        }

        $existingRequest = CheckoutRequest::query()
            ->where(
                'resident_stay_id',
                $stay->id
            )
            ->active()
            ->exists();

        if ($existingRequest) {
            return back()->with(
                'error',
                'An active checkout request already exists for your current stay.'
            );
        }

        $requestedDate = Carbon::parse(
            $validated['requested_checkout_date']
        )->startOfDay();

        $today = today()->startOfDay();

        $requiredNoticeDays = 30;

        $minimumRecommendedDate = $today
            ->copy()
            ->addDays(
                $requiredNoticeDays
            );

        $actualNoticeDays = max(
            0,
            $today->diffInDays(
                $requestedDate,
                false
            )
        );

        $isShortNotice = $requestedDate->lt(
            $minimumRecommendedDate
        );

        $warningAccepted = $request->boolean(
            'short_notice_warning_accepted'
        );

        if (
            $isShortNotice
            && !$warningAccepted
        ) {
            throw ValidationException::withMessages([
                'short_notice_warning_accepted' =>
                    'You must accept the short-notice checkout policy before submitting this request.',
            ]);
        }

        $outstandingAmount =
            $this->calculateOutstandingAmount(
                $resident
            );

        DB::transaction(function () use (
            $resident,
            $stay,
            $validated,
            $requestedDate,
            $requiredNoticeDays,
            $actualNoticeDays,
            $isShortNotice,
            $warningAccepted,
            $outstandingAmount
        ): void {
            $checkoutRequest =
                CheckoutRequest::create([
                    'resident_id' =>
                        $resident->id,

                    'resident_stay_id' =>
                        $stay->id,

                    'requested_by_type' =>
                        'resident',

                    'requested_by_id' =>
                        $resident->id,

                    'requested_checkout_date' =>
                        $requestedDate->toDateString(),

                    'requested_at' =>
                        now(),

                    'required_notice_days' =>
                        $requiredNoticeDays,

                    'actual_notice_days' =>
                        $actualNoticeDays,

                    'is_short_notice' =>
                        $isShortNotice,

                    'short_notice_warning_accepted' =>
                        $isShortNotice
                        && $warningAccepted,

                    'warning_accepted_at' =>
                        $isShortNotice
                        && $warningAccepted
                            ? now()
                            : null,

                    'reason' =>
                        trim(
                            $validated['reason']
                        ),

                    'resident_notes' =>
                        filled(
                            $validated['resident_notes']
                            ?? null
                        )
                            ? trim(
                                $validated['resident_notes']
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
                    $resident,

                notes:
                    'Checkout request submitted by resident.',

                metadata: [
                    'requested_checkout_date' =>
                        $requestedDate->toDateString(),

                    'required_notice_days' =>
                        $requiredNoticeDays,

                    'actual_notice_days' =>
                        $actualNoticeDays,

                    'is_short_notice' =>
                        $isShortNotice,

                    'outstanding_amount_at_request' =>
                        $outstandingAmount,
                ]
            );

            if (
                $isShortNotice
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
                        $resident,

                    notes:
                        'Resident accepted the short-notice checkout policy.',

                    metadata: [
                        'actual_notice_days' =>
                            $actualNoticeDays,

                        'required_notice_days' =>
                            $requiredNoticeDays,
                    ]
                );
            }
        });

        return back()->with(
            'success',
            $isShortNotice
                ? 'Checkout request submitted with a short-notice policy acknowledgement.'
                : 'Checkout request submitted successfully.'
        );
    }

    public function cancel(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        abort_unless(
            (int) $checkoutRequest->resident_id
                === (int) $resident->id,
            403
        );

        if (
            !$checkoutRequest
                ->canBeCancelledByResident()
        ) {
            return back()->with(
                'error',
                'This checkout request can no longer be cancelled from the resident portal.'
            );
        }

        $validated = $request->validate([
            'cancellation_reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use (
            $checkoutRequest,
            $resident,
            $validated
        ): void {
            $fromStatus =
                $checkoutRequest->status;

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_CANCELLED,

                'cancelled_by_type' =>
                    'resident',

                'cancelled_by_id' =>
                    $resident->id,

                'cancelled_at' =>
                    now(),

                'cancellation_reason' =>
                    trim(
                        $validated[
                            'cancellation_reason'
                        ]
                    ),
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                    $checkoutRequest,

                action:
                    'request_cancelled',

                fromStatus:
                    $fromStatus,

                toStatus:
                    CheckoutRequest::STATUS_CANCELLED,

                actor:
                    $resident,

                notes:
                    trim(
                        $validated[
                            'cancellation_reason'
                        ]
                    )
            );
        });

        return back()->with(
            'success',
            'Checkout request cancelled successfully.'
        );
    }

    protected function transformRequest(
        CheckoutRequest $checkoutRequest
    ): array {
        return [
            'id' =>
                $checkoutRequest->id,

            'resident_stay_id' =>
                $checkoutRequest
                    ->resident_stay_id,

            'requested_checkout_date' =>
                $checkoutRequest
                    ->requested_checkout_date
                    ?->toDateString(),

            'requested_at' =>
                $checkoutRequest->requested_at,

            'requested_by_type' =>
                $checkoutRequest
                    ->requested_by_type,

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

            'warning_accepted_at' =>
                $checkoutRequest
                    ->warning_accepted_at,

            'short_notice_charge' =>
                (float) $checkoutRequest
                    ->short_notice_charge,

            'short_notice_charge_final' =>
                (float) $checkoutRequest
                    ->short_notice_charge_final,

            'reason' =>
                $checkoutRequest->reason,

            'resident_notes' =>
                $checkoutRequest
                    ->resident_notes,

            'status' =>
                $checkoutRequest->status,

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

            'warden_review_notes' =>
                $checkoutRequest
                    ->warden_review_notes,

            'warden_reviewed_at' =>
                $checkoutRequest
                    ->warden_reviewed_at,

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

            'total_checkout_charges' =>
                $checkoutRequest
                    ->totalCheckoutCharges(),

            'final_approved_at' =>
                $checkoutRequest
                    ->final_approved_at,

            'final_approval_notes' =>
                $checkoutRequest
                    ->final_approval_notes,

            'exit_token_available' =>
                filled(
                    $checkoutRequest->exit_token
                ),

            'exit_token_expires_at' =>
                $checkoutRequest
                    ->exit_token_expires_at,

            'gate_verified_at' =>
                $checkoutRequest
                    ->gate_verified_at,

            'actual_checkout_at' =>
                $checkoutRequest
                    ->actual_checkout_at,

            'cancellation_reason' =>
                $checkoutRequest
                    ->cancellation_reason,

            'cancelled_at' =>
                $checkoutRequest
                    ->cancelled_at,

            'can_cancel' =>
                $checkoutRequest
                    ->canBeCancelledByResident(),

            'stay' => [
                'building_name' =>
                    $checkoutRequest
                        ->stay
                        ?->building
                        ?->name,

                'floor_name' =>
                    $checkoutRequest
                        ->stay
                        ?->floor
                        ?->name
                    ?? $checkoutRequest
                        ->stay
                        ?->floor
                        ?->floor_number,

                'room_number' =>
                    $checkoutRequest
                        ->stay
                        ?->room
                        ?->room_number,

                'bed_number' =>
                    $checkoutRequest
                        ->stay
                        ?->bed
                        ?->bed_number,

                'check_in_date' =>
                    $checkoutRequest
                        ->stay
                        ?->check_in_date
                        ?->toDateString(),
            ],

            'histories' =>
                $checkoutRequest
                    ->histories
                    ->sortBy('created_at')
                    ->values()
                    ->map(
                        fn ($history) => [
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
                                $history->metadata,

                            'created_at' =>
                                $history
                                    ->created_at,
                        ]
                    ),
        ];
    }

    protected function outstandingSummary(
        Resident $resident
    ): array {
        $query = FeeInvoice::query()
            ->where(
                'resident_id',
                $resident->id
            )
            ->whereIn('status', [
                'unpaid',
                'partial',
                'overdue',
            ]);

        $invoices = $query->get([
            'id',
            'amount',
            'paid_amount',
            'status',
        ]);

        return [
            'invoice_count' =>
                $invoices->count(),

            'amount' =>
                $invoices->sum(
                    fn (FeeInvoice $invoice) =>
                        max(
                            0,
                            (float) $invoice->amount
                            - (float) $invoice
                                ->paid_amount
                        )
                ),
        ];
    }

    protected function calculateOutstandingAmount(
        Resident $resident
    ): float {
        return round(
            (float) $this
                ->outstandingSummary($resident)[
                    'amount'
                ],
            2
        );
    }

    public function exitPass(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): Response {
        /** @var Resident $resident */
        $resident = $request->user('resident');

        abort_unless($resident, 401);

        abort_unless(
            (int) $checkoutRequest->resident_id
                === (int) $resident->id,
            403,
            'This exit pass does not belong to you.'
        );

        if (
            $checkoutRequest->status !==
            CheckoutRequest::STATUS_READY_FOR_EXIT
        ) {
            abort(
                403,
                'The exit pass is available only after final checkout approval.'
            );
        }

        if (!$checkoutRequest->exit_token) {
            abort(
                404,
                'No active exit code is available for this checkout request.'
            );
        }

        $checkoutRequest->load([
            'stay:id,resident_id,building_id,floor_id,room_id,bed_id,check_in_date,expected_check_out_date,status',

            'stay.building:id,name',
            'stay.floor:id,name,floor_number',
            'stay.room:id,room_number',
            'stay.bed:id,bed_number',

            'finalApprover:id,name',

            'histories' => fn ($query) =>
                $query
                    ->oldest('created_at')
                    ->oldest('id'),
        ]);

        return Inertia::render(
            'ResidentPortal/CheckoutRequests/ExitPass',
            [
                'exitPass' => [
                    'id' =>
                        $checkoutRequest->id,

                    'status' =>
                        $checkoutRequest->status,

                    'requested_checkout_date' =>
                        $checkoutRequest
                            ->requested_checkout_date
                            ?->toDateString(),

                    'requested_at' =>
                        $checkoutRequest->requested_at,

                    'reason' =>
                        $checkoutRequest->reason,

                    'exit_token' =>
                        $checkoutRequest->exit_token,

                    'exit_token_generated_at' =>
                        $checkoutRequest
                            ->exit_token_generated_at,

                    'exit_token_expires_at' =>
                        $checkoutRequest
                            ->exit_token_expires_at,

                    'dues_clearance_status' =>
                        $checkoutRequest
                            ->dues_clearance_status,

                    'short_notice_charge_final' =>
                        (float) $checkoutRequest
                            ->short_notice_charge_final,

                    'asset_damage_charge' =>
                        (float) $checkoutRequest
                            ->asset_damage_charge,

                    'other_checkout_charge' =>
                        (float) $checkoutRequest
                            ->other_checkout_charge,

                    'total_checkout_charges' =>
                        $checkoutRequest
                            ->totalCheckoutCharges(),

                    'final_approval_notes' =>
                        $checkoutRequest
                            ->final_approval_notes,

                    'final_approved_at' =>
                        $checkoutRequest
                            ->final_approved_at,

                    'final_approver' =>
                        $checkoutRequest
                            ->finalApprover
                            ? [
                                'id' =>
                                    $checkoutRequest
                                        ->finalApprover
                                        ->id,

                                'name' =>
                                    $checkoutRequest
                                        ->finalApprover
                                        ->name,
                            ]
                            : null,

                    'resident' => [
                        'id' =>
                            $resident->id,

                        'resident_code' =>
                            $resident->resident_code,

                        'name' => trim(
                            $resident->first_name
                            . ' '
                            . $resident->last_name
                        ),

                        'first_name' =>
                            $resident->first_name,

                        'last_name' =>
                            $resident->last_name,

                        'phone' =>
                            $resident->phone,

                        'photo_url' =>
                            $resident->photo_url,

                        'status' =>
                            $resident->status,
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

                        'status' =>
                            $checkoutRequest
                                ->stay?->status,
                    ],

                    'histories' =>
                        $checkoutRequest
                            ->histories
                            ->map(
                                fn ($history) => [
                                    'id' =>
                                        $history->id,

                                    'action' =>
                                        $history->action,

                                    'notes' =>
                                        $history->notes,

                                    'created_at' =>
                                        $history->created_at,
                                ]
                            )
                            ->values(),
                ],
            ]
        );
    }
}
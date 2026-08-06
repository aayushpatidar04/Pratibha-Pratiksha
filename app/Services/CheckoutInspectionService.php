<?php

namespace App\Services;

use App\Models\CheckoutInventoryReview;
use App\Models\CheckoutRequest;
use App\Models\ResidentInventoryAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutInspectionService
{
    public function initialize(
        CheckoutRequest $checkoutRequest,
        User $warden
    ): CheckoutRequest {
        $this->ensureAssignedWarden(
            $checkoutRequest,
            $warden
        );

        if (
            !in_array(
                $checkoutRequest->status,
                [
                    CheckoutRequest::STATUS_ASSIGNED_TO_WARDEN,
                    CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'request' =>
                    'This checkout request cannot be inspected in its current status.',
            ]);
        }

        return DB::transaction(function () use ($checkoutRequest, $warden) {
            $fromStatus = $checkoutRequest->status;

            $assignments =
                ResidentInventoryAssignment::query()
                    ->where(
                        'resident_stay_id',
                        $checkoutRequest->resident_stay_id
                    )
                    ->where(
                        'resident_id',
                        $checkoutRequest->resident_id
                    )
                    ->where('is_returned', false)
                    ->get();

            foreach ($assignments as $assignment) {
                CheckoutInventoryReview::firstOrCreate(
                    [
                        'checkout_request_id' =>
                            $checkoutRequest->id,

                        'resident_inventory_assignment_id' =>
                            $assignment->id,
                    ],
                    [
                        'inventory_id' =>
                            $assignment->inventory_id,

                        'assigned_quantity' =>
                            $assignment->quantity,

                        'returned_good_quantity' => 0,

                        'returned_damaged_quantity' => 0,

                        'missing_quantity' => 0,

                        'damage_charge' => 0,
                    ]
                );
            }

            if (
                $checkoutRequest->status !==
                CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS
            ) {
                $checkoutRequest->update([
                    'status' =>
                        CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS,

                    'warden_review_status' =>
                        'in_progress',
                ]);

                CheckoutRequestHistoryService::record(
                    checkoutRequest: $checkoutRequest,
                    action: 'warden_review_started',
                    fromStatus: $fromStatus,
                    toStatus:
                    CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS,
                    actor: $warden,
                    notes:
                    'Warden started room and asset inspection.'
                );
            }

            return $checkoutRequest->fresh([
                'inventoryReviews.inventory',
                'inventoryReviews.assignment',
            ]);
        });
    }

    public function saveDraft(
        CheckoutRequest $checkoutRequest,
        User $warden,
        array $validated
    ): CheckoutRequest {
        $this->ensureAssignedWarden(
            $checkoutRequest,
            $warden
        );

        if (
            !in_array(
                $checkoutRequest->status,
                [
                    CheckoutRequest::STATUS_ASSIGNED_TO_WARDEN,
                    CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS,
                    CheckoutRequest::STATUS_ON_HOLD,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'request' =>
                    'This inspection cannot be modified in its current status.',
            ]);
        }

        return DB::transaction(function () use ($checkoutRequest, $warden, $validated) {
            if (
                $checkoutRequest->status ===
                CheckoutRequest::STATUS_ASSIGNED_TO_WARDEN
            ) {
                $this->initialize(
                    $checkoutRequest,
                    $warden
                );

                $checkoutRequest->refresh();
            }

            foreach (
                $validated['inventory_reviews'] ?? []
                as $row
            ) {
                $review =
                    CheckoutInventoryReview::query()
                        ->where(
                            'checkout_request_id',
                            $checkoutRequest->id
                        )
                        ->whereKey($row['id'])
                        ->firstOrFail();

                $this->validateQuantities(
                    $review,
                    $row,
                    false
                );

                $review->update([
                    'returned_good_quantity' =>
                        (int) $row[
                            'returned_good_quantity'
                        ],

                    'returned_damaged_quantity' =>
                        (int) $row[
                            'returned_damaged_quantity'
                        ],

                    'missing_quantity' =>
                        (int) $row[
                            'missing_quantity'
                        ],

                    'condition_at_review' =>
                        $row['condition_at_review']
                        ?? null,

                    'review_notes' =>
                        filled(
                            $row['review_notes']
                            ?? null
                        )
                        ? trim(
                            $row['review_notes']
                        )
                        : null,

                    'damage_charge' =>
                        (float) (
                            $row['damage_charge']
                            ?? 0
                        ),

                    'reviewed_by' =>
                        $warden->id,

                    'reviewed_at' =>
                        now(),
                ]);
            }

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS,

                'warden_review_status' =>
                    'in_progress',

                'warden_review_notes' =>
                    filled(
                        $validated[
                            'warden_review_notes'
                        ] ?? null
                    )
                    ? trim(
                        $validated[
                            'warden_review_notes'
                        ]
                    )
                    : null,
            ]);

            $damageCharge =
                CheckoutInventoryReview::query()
                    ->where(
                        'checkout_request_id',
                        $checkoutRequest->id
                    )
                    ->sum('damage_charge');

            $checkoutRequest->update([
                'asset_damage_charge' =>
                    $damageCharge,
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest: $checkoutRequest,
                action: 'inventory_review_saved',
                fromStatus:
                CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS,
                toStatus:
                CheckoutRequest::STATUS_WARDEN_REVIEW_IN_PROGRESS,
                actor: $warden,
                notes:
                'Warden saved the inspection draft.',
                metadata: [
                    'asset_damage_charge' =>
                        (float) $damageCharge,
                ]
            );

            return $checkoutRequest->fresh([
                'inventoryReviews.inventory',
            ]);
        });
    }

    public function approve(
        CheckoutRequest $checkoutRequest,
        User $warden,
        array $validated
    ): CheckoutRequest {
        $this->ensureAssignedWarden(
            $checkoutRequest,
            $warden
        );

        return DB::transaction(function () use ($checkoutRequest, $warden, $validated) {
            $this->saveDraft(
                $checkoutRequest,
                $warden,
                $validated
            );

            $reviews =
                CheckoutInventoryReview::query()
                    ->where(
                        'checkout_request_id',
                        $checkoutRequest->id
                    )
                    ->get();

            foreach ($reviews as $review) {
                $this->validateQuantities(
                    $review,
                    [
                        'returned_good_quantity' =>
                            $review
                                ->returned_good_quantity,

                        'returned_damaged_quantity' =>
                            $review
                                ->returned_damaged_quantity,

                        'missing_quantity' =>
                            $review
                                ->missing_quantity,
                    ],
                    true
                );
            }

            $fromStatus =
                $checkoutRequest->fresh()->status;

            $damageCharge =
                $reviews->sum('damage_charge');

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_WARDEN_APPROVED,

                'warden_review_status' =>
                    'approved',

                'warden_reviewed_at' =>
                    now(),

                'warden_review_notes' =>
                    filled(
                        $validated[
                            'warden_review_notes'
                        ] ?? null
                    )
                    ? trim(
                        $validated[
                            'warden_review_notes'
                        ]
                    )
                    : null,

                'asset_damage_charge' =>
                    $damageCharge,
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest: $checkoutRequest,
                action: 'warden_approved',
                fromStatus: $fromStatus,
                toStatus:
                CheckoutRequest::STATUS_WARDEN_APPROVED,
                actor: $warden,
                notes:
                $checkoutRequest
                    ->warden_review_notes
                ?? 'Warden completed and approved the inspection.',
                metadata: [
                    'asset_damage_charge' =>
                        (float) $damageCharge,

                    'asset_types_reviewed' =>
                        $reviews->count(),

                    'damaged_quantity' =>
                        $reviews->sum(
                            'returned_damaged_quantity'
                        ),

                    'missing_quantity' =>
                        $reviews->sum(
                            'missing_quantity'
                        ),
                ]
            );

            return $checkoutRequest->fresh([
                'inventoryReviews.inventory',
            ]);
        });
    }

    public function hold(
        CheckoutRequest $checkoutRequest,
        User $warden,
        string $notes
    ): CheckoutRequest {
        $this->ensureAssignedWarden(
            $checkoutRequest,
            $warden
        );

        return DB::transaction(function () use ($checkoutRequest, $warden, $notes) {
            $fromStatus =
                $checkoutRequest->status;

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_ON_HOLD,

                'warden_review_status' =>
                    'hold',

                'warden_review_notes' =>
                    trim($notes),

                'warden_reviewed_at' =>
                    now(),
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest: $checkoutRequest,
                action: 'warden_put_on_hold',
                fromStatus: $fromStatus,
                toStatus:
                CheckoutRequest::STATUS_ON_HOLD,
                actor: $warden,
                notes: trim($notes)
            );

            return $checkoutRequest;
        });
    }

    public function reject(
        CheckoutRequest $checkoutRequest,
        User $warden,
        string $notes
    ): CheckoutRequest {
        $this->ensureAssignedWarden(
            $checkoutRequest,
            $warden
        );

        return DB::transaction(function () use ($checkoutRequest, $warden, $notes) {
            $fromStatus =
                $checkoutRequest->status;

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_WARDEN_REJECTED,

                'warden_review_status' =>
                    'rejected',

                'warden_review_notes' =>
                    trim($notes),

                'warden_reviewed_at' =>
                    now(),
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest: $checkoutRequest,
                action: 'warden_rejected',
                fromStatus: $fromStatus,
                toStatus:
                CheckoutRequest::STATUS_WARDEN_REJECTED,
                actor: $warden,
                notes: trim($notes)
            );

            return $checkoutRequest;
        });
    }

    protected function ensureAssignedWarden(
        CheckoutRequest $checkoutRequest,
        User $warden
    ): void {
        if (
            (int) $checkoutRequest
                ->assigned_warden_id
            !== (int) $warden->id
        ) {
            abort(
                403,
                'This checkout request is not assigned to you.'
            );
        }
    }

    protected function validateQuantities(
        CheckoutInventoryReview $review,
        array $row,
        bool $requireComplete
    ): void {
        $good =
            (int) (
                $row[
                    'returned_good_quantity'
                ] ?? 0
            );

        $damaged =
            (int) (
                $row[
                    'returned_damaged_quantity'
                ] ?? 0
            );

        $missing =
            (int) (
                $row['missing_quantity']
                ?? 0
            );

        $reviewed =
            $good + $damaged + $missing;

        if (
            $reviewed >
            (int) $review->assigned_quantity
        ) {
            throw ValidationException::withMessages([
                'inventory_reviews' =>
                    "Reviewed quantities for asset #{$review->id} exceed the assigned quantity.",
            ]);
        }

        if (
            $requireComplete
            && $reviewed !==
            (int) $review->assigned_quantity
        ) {
            throw ValidationException::withMessages([
                'inventory_reviews' =>
                    "Every issued quantity must be marked as returned good, damaged, or missing before approval.",
            ]);
        }
    }
}
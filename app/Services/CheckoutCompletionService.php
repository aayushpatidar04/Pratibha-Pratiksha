<?php

namespace App\Services;

use App\Models\Bed;
use App\Models\Building;
use App\Models\CheckoutInventoryReview;
use App\Models\CheckoutRequest;
use App\Models\Inventory;
use App\Models\ResidentInventoryAssignment;
use App\Models\ResidentStay;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutCompletionService
{
    /**
     * Validate an exit token without modifying any records.
     */
    public function verifyToken(
        string $token
    ): CheckoutRequest {
        $token = trim($token);

        if ($token === '') {
            throw ValidationException::withMessages([
                'exit_token' =>
                    'Enter a valid exit code.',
            ]);
        }

        $checkoutRequest =
            CheckoutRequest::query()
                ->with([
                    'resident:id,resident_code,first_name,last_name,phone,photo_url,status',

                    'stay:id,resident_id,building_id,floor_id,room_id,bed_id,check_in_date,status,check_in_status',

                    'stay.building:id,name',
                    'stay.floor:id,name,floor_number',
                    'stay.room:id,room_number',
                    'stay.bed:id,bed_number',

                    'assignedWarden:id,name,email',
                    'finalApprover:id,name,email',
                ])
                ->where(
                    'exit_token',
                    $token
                )
                ->first();

        if (!$checkoutRequest) {
            throw ValidationException::withMessages([
                'exit_token' =>
                    'The exit code is invalid.',
            ]);
        }

        $this->validateReadyForExit(
            $checkoutRequest
        );

        return $checkoutRequest;
    }

    /**
     * Complete physical checkout after guard verification.
     */
    public function complete(
        CheckoutRequest $checkoutRequest,
        User $guard,
        array $data = []
    ): CheckoutRequest {
        return DB::transaction(function () use ($checkoutRequest, $guard, $data): CheckoutRequest {
            $checkoutRequest =
                CheckoutRequest::query()
                    ->with([
                        'resident',
                        'inventoryReviews',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $checkoutRequest->id
                    );

            /*
             * Idempotency protection.
             */
            if (
                $checkoutRequest->status ===
                CheckoutRequest::STATUS_COMPLETED
            ) {
                throw ValidationException::withMessages([
                    'checkout_request' =>
                        'Checkout has already been completed.',
                ]);
            }

            $this->validateReadyForExit(
                $checkoutRequest
            );

            $stay =
                ResidentStay::query()
                    ->with([
                        'resident',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $checkoutRequest
                            ->resident_stay_id
                    );

            if (
                $stay->status !== 'active'
                || !$stay->check_in_status
            ) {
                throw ValidationException::withMessages([
                    'checkout_request' =>
                        'The resident no longer has an active checked-in stay.',
                ]);
            }

            if (
                (int) $stay->resident_id !==
                (int) $checkoutRequest->resident_id
            ) {
                throw ValidationException::withMessages([
                    'checkout_request' =>
                        'The checkout request does not match the resident stay.',
                ]);
            }

            $this->finalizeInventory(
                $checkoutRequest,
                $stay,
                $guard
            );

            $this->releaseAccommodation(
                $stay
            );

            $completionNotes =
                filled(
                    $data['completion_notes']
                    ?? null
                )
                ? trim(
                    $data['completion_notes']
                )
                : null;

            $gateNotes =
                filled(
                    $data['gate_verification_notes']
                    ?? null
                )
                ? trim(
                    $data[
                        'gate_verification_notes'
                    ]
                )
                : null;

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_COMPLETED,

                'gate_verified_by' =>
                    $guard->id,

                'gate_verified_at' =>
                    now(),

                'gate_verification_notes' =>
                    $gateNotes,

                'actual_checkout_at' =>
                    now(),

                'completed_by' =>
                    $guard->id,

                'completion_notes' =>
                    $completionNotes,

                /*
                 * Invalidate token after use.
                 */
                'exit_token' =>
                    null,
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                $checkoutRequest,

                action:
                'gate_exit_verified',

                fromStatus:
                CheckoutRequest::STATUS_READY_FOR_EXIT,

                toStatus:
                CheckoutRequest::STATUS_READY_FOR_EXIT,

                actor:
                $guard,

                notes:
                $gateNotes
                ?? 'Gate staff verified the resident exit authorization.'
            );

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                $checkoutRequest,

                action:
                'checkout_completed',

                fromStatus:
                CheckoutRequest::STATUS_READY_FOR_EXIT,

                toStatus:
                CheckoutRequest::STATUS_COMPLETED,

                actor:
                $guard,

                notes:
                $completionNotes
                ?? 'Resident physically exited the hostel and checkout was completed.',

                metadata: [
                    'resident_id' =>
                        $checkoutRequest
                            ->resident_id,

                    'resident_stay_id' =>
                        $stay->id,

                    'room_id' =>
                        $stay->room_id,

                    'bed_id' =>
                        $stay->bed_id,

                    'actual_checkout_at' =>
                        now()
                            ->toDateTimeString(),
                ]
            );

            return $checkoutRequest->fresh([
                'resident',
                'stay.building',
                'stay.floor',
                'stay.room',
                'stay.bed',
                'inventoryReviews.inventory',
                'histories',
            ]);
        });
    }

    protected function validateReadyForExit(
        CheckoutRequest $checkoutRequest
    ): void {
        if (
            $checkoutRequest->status !==
            CheckoutRequest::STATUS_READY_FOR_EXIT
        ) {
            throw ValidationException::withMessages([
                'exit_token' =>
                    'This checkout request is not ready for exit.',
            ]);
        }

        if (
            !$checkoutRequest->final_approved_at
            || !$checkoutRequest->final_approved_by
        ) {
            throw ValidationException::withMessages([
                'exit_token' =>
                    'Final administration approval is missing.',
            ]);
        }

        if (
            $checkoutRequest->warden_review_status
            !== 'approved'
        ) {
            throw ValidationException::withMessages([
                'exit_token' =>
                    'The checkout inspection is not approved.',
            ]);
        }

        if (
            !in_array(
                $checkoutRequest
                    ->dues_clearance_status,
                [
                    'clear',
                    'waived',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'exit_token' =>
                    'The resident dues are not cleared.',
            ]);
        }

        if (!$checkoutRequest->exit_token) {
            throw ValidationException::withMessages([
                'exit_token' =>
                    'This checkout request does not have an active exit code.',
            ]);
        }

        if (
            !$checkoutRequest
                ->exit_token_expires_at
        ) {
            throw ValidationException::withMessages([
                'exit_token' =>
                    'The exit code expiry is not configured.',
            ]);
        }

        if (
            now()->greaterThan(
                $checkoutRequest
                    ->exit_token_expires_at
            )
        ) {
            throw ValidationException::withMessages([
                'exit_token' =>
                    'The exit code has expired. Ask administration to regenerate it.',
            ]);
        }
    }

    protected function finalizeInventory(
        CheckoutRequest $checkoutRequest,
        ResidentStay $stay,
        User $guard
    ): void {
        $reviews =
            CheckoutInventoryReview::query()
                ->where(
                    'checkout_request_id',
                    $checkoutRequest->id
                )
                ->lockForUpdate()
                ->get();

        $activeAssignments =
            ResidentInventoryAssignment::query()
                ->where(
                    'resident_stay_id',
                    $stay->id
                )
                ->where('is_returned', false)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

        /*
         * Every active assignment must have a completed review.
         */
        foreach (
            $activeAssignments
            as $assignment
        ) {
            $review =
                $reviews->firstWhere(
                    'resident_inventory_assignment_id',
                    $assignment->id
                );

            if (!$review) {
                throw ValidationException::withMessages([
                    'inventory' =>
                        "Inventory review is missing for assignment #{$assignment->id}.",
                ]);
            }

            if (!$review->isFullyReviewed()) {
                throw ValidationException::withMessages([
                    'inventory' =>
                        "Inventory review for assignment #{$assignment->id} is incomplete.",
                ]);
            }

            if (
                (int) $review->assigned_quantity
                !== (int) $assignment->quantity
            ) {
                throw ValidationException::withMessages([
                    'inventory' =>
                        "Reviewed quantity for assignment #{$assignment->id} does not match the issued quantity.",
                ]);
            }

            $good =
                (int) $review
                    ->returned_good_quantity;

            $damaged =
                (int) $review
                    ->returned_damaged_quantity;

            $missing =
                (int) $review
                    ->missing_quantity;

            $issued =
                (int) $assignment->quantity;

            $inventory =
                Inventory::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $assignment
                            ->inventory_id
                    );

            /*
             * Remove all issued quantity from in-use.
             */
            $inventory->in_use = max(
                0,
                (int) $inventory->in_use
                - $issued
            );

            /*
             * Damaged and missing quantities leave usable stock.
             */
            $inventory->damaged =
                (int) $inventory->damaged
                + $damaged;

            $inventory->missing =
                (int) $inventory->missing
                + $missing;

            /*
             * Recalculate available from the stock equation.
             * Good returns automatically become available.
             */
            $inventory->available = max(
                0,
                (int) $inventory
                    ->total_quantity
                - (int) $inventory->in_use
                - (int) $inventory->damaged
                - (int) $inventory->missing
            );

            $inventory->save();

            $conditionAtReturn = match (true) {
                $missing > 0 =>
                'missing',

                $damaged > 0 =>
                'damaged',

                default =>
                'good',
            };

            $assignment->update([
                'is_returned' =>
                    true,

                'returned_quantity' =>
                    $good + $damaged,

                'returned_good_quantity' =>
                    $good,

                'returned_damaged_quantity' =>
                    $damaged,

                'missing_quantity' =>
                    $missing,

                'condition_at_return' =>
                    $conditionAtReturn,

                'return_notes' =>
                    $review->review_notes,

                'returned_at' =>
                    now(),

                'returned_by' =>
                    $guard->id,

                'return_review_status' =>
                    'approved',
            ]);
        }

        /*
         * Any review pointing to a returned or unrelated assignment
         * is invalid.
         */
        foreach ($reviews as $review) {
            if (
                !$activeAssignments->has(
                    $review
                        ->resident_inventory_assignment_id
                )
            ) {
                throw ValidationException::withMessages([
                    'inventory' =>
                        "Inventory review #{$review->id} does not belong to an active assignment for this stay.",
                ]);
            }
        }
    }

    protected function releaseAccommodation(
        ResidentStay $stay
    ): void {
        $bed =
            Bed::query()
                ->lockForUpdate()
                ->find(
                    $stay->bed_id
                );

        $room =
            Room::query()
                ->lockForUpdate()
                ->find(
                    $stay->room_id
                );

        /*
         * Close stay.
         */
        $stay->update([
            'status' =>
                'ended',

            'actual_check_out_date' =>
                now()->toDateString(),

            'check_in_status' =>
                false,

            'checkout_status' =>
                'approved',

            'checkout_reviewed_at' =>
                now(),
        ]);

        /*
         * Release bed only if it still belongs to this resident.
         */
        if (
            $bed
            && (
                !$bed->resident_id
                || (int) $bed->resident_id
                === (int) $stay->resident_id
            )
        ) {
            $bed->update([
                'status' =>
                    'vacant',

                'resident_id' =>
                    null,
            ]);
        }

        if ($room) {
            $newOccupancy = max(
                0,
                (int) $room->occupied_beds
                - 1
            );

            $roomStatus = match (true) {
                $room->status ===
                'maintenance' =>
                'maintenance',

                $newOccupancy <= 0 =>
                'available',

                $newOccupancy >=
                (int) $room->capacity =>
                'occupied',

                default =>
                'partially_occupied',
            };

            $room->update([
                'occupied_beds' =>
                    $newOccupancy,

                'status' =>
                    $roomStatus,
            ]);
        }

        /*
         * Avoid a negative building occupancy count.
         */
        Building::query()
            ->whereKey(
                $stay->building_id
            )
            ->where(
                'occupied',
                '>',
                0
            )
            ->decrement(
                'occupied'
            );

        $stay->resident?->update([
            'status' =>
                'left',
        ]);
    }
}
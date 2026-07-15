<?php

namespace App\Services;

use App\Models\Bed;
use App\Models\Building;
use App\Models\Inventory;
use App\Models\Resident;
use App\Models\ResidentInventoryAssignment;
use App\Models\ResidentStay;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomAllotmentService
{
    /**
     * Allot a bed to a resident: creates a ResidentStay, marks the bed occupied,
     * and keeps room/building occupancy counters in sync.
     */
    public static function allot(Resident $resident, array $data): ResidentStay
    {
        return DB::transaction(function () use ($resident, $data) {
            $bed = Bed::lockForUpdate()->findOrFail($data['bed_id']);

            if ($bed->status === 'occupied') {
                throw new \RuntimeException('This bed is already occupied.');
            }

            $room = Room::findOrFail($data['room_id']);

            $stay = ResidentStay::create([
                'resident_id' => $resident->id,
                'building_id' => $data['building_id'],
                'floor_id' => $data['floor_id'],
                'room_id' => $data['room_id'],
                'bed_id' => $data['bed_id'],
                'check_in_date' => $data['check_in_date'] ?? now()->toDateString(),
                'check_in_status' => false,
                'checked_in_at' => null,
                'checked_in_by' => null,
                'expected_check_out_date' => $data['expected_check_out_date'] ?? null,
                'rent_amount' => $data['rent_amount'] ?? $room->monthly_rent_per_bed,
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'bill_type' => $data['bill_type'] ?? 'monthly',
                'status' => 'upcoming',
                'notes' => $data['notes'] ?? null,
                'billing_basis' => $data['billing_basis'] ?? 'monthly',
                'daily_rate' => $data['daily_rate'] ?? null,
            ]);

            if ((float) $stay->deposit_amount > 0) {
                app(\App\Services\SecurityDepositBillingService::class)
                    ->createInvoice($stay);
            }

            // if ($stay->billing_basis === 'daily') {
            //     app(\App\Services\ShortStayBillingService::class)
            //         ->createOrUpdateInvoice($stay);
            // }

            $bed->update(['status' => 'occupied', 'resident_id' => $resident->id]);

            $room->increment('occupied_beds');
            $room->refresh();
            $room->update([
                'status' => $room->occupied_beds >= $room->capacity ? 'occupied' : 'partially_occupied',
            ]);

            Building::where('id', $data['building_id'])->increment('occupied');

            return $stay;
        });
    }

    public static function confirmCheckIn(
        ResidentStay $stay,
        array $data
    ): ResidentStay {
        return DB::transaction(function () use ($stay, $data) {
            $stay = ResidentStay::query()
                ->with([
                    'resident',
                    'room',
                    'bed',
                    'inventoryAssignments',
                ])
                ->lockForUpdate()
                ->findOrFail($stay->id);

            if ($stay->status === 'completed') {
                throw new \RuntimeException(
                    'This stay has already been completed.'
                );
            }

            if ($stay->check_in_status) {
                throw new \RuntimeException(
                    'This resident has already checked in.'
                );
            }

            $checkInDate = Carbon::parse(
                $data['check_in_date']
                ?? $stay->check_in_date
                ?? now()
            )->startOfDay();

            $assignments = collect(
                $data['inventory'] ?? []
            )->filter(function ($item) {
                return !empty($item['inventory_id'])
                    && (int) ($item['quantity'] ?? 0) > 0;
            });

            foreach ($assignments as $assignment) {
                $inventory = Inventory::query()
                    ->lockForUpdate()
                    ->findOrFail($assignment['inventory_id']);

                if ($inventory->category !== 'student') {
                    throw ValidationException::withMessages([
                        'inventory' =>
                            "{$inventory->item_name} is not a student inventory item.",
                    ]);
                }

                $quantity = (int) $assignment['quantity'];

                if ($quantity > (int) $inventory->available) {
                    throw ValidationException::withMessages([
                        'inventory' =>
                            "Only {$inventory->available} {$inventory->unit} of {$inventory->item_name} are available.",
                    ]);
                }

                ResidentInventoryAssignment::create([
                    'resident_id' => $stay->resident_id,
                    'resident_stay_id' => $stay->id,
                    'inventory_id' => $inventory->id,
                    'quantity' => $quantity,
                    'condition_at_issue' =>
                        $assignment['condition_at_issue']
                        ?? 'good',
                    'issue_notes' =>
                        $assignment['notes'] ?? null,
                    'assigned_at' => now(),
                    'assigned_by' => Auth::id(),
                    'is_returned' => false,
                    'returned_quantity' => 0,
                ]);

                $inventory->increment('in_use', $quantity);
                $inventory->refresh();

                $inventory->update([
                    'available' => max(
                        0,
                        (int) $inventory->total_quantity
                        - (int) $inventory->in_use
                        - (int) $inventory->damaged
                    ),
                ]);
            }

            $stay->update([
                'check_in_date' => $checkInDate->toDateString(),
                'check_in_status' => true,
                'checked_in_at' => now(),
                'checked_in_by' => Auth::id(),
                'status' => 'active',
            ]);

            $stay->resident?->update([
                'status' => 'active',
            ]);

            /*
             * Create financial records only after physical arrival.
             */

            if ($stay->billing_basis === 'daily') {
                app(ShortStayBillingService::class)
                    ->createOrUpdateInvoice($stay);
            }

            return $stay->fresh([
                'resident',
                'building',
                'floor',
                'room',
                'bed',
                'inventoryAssignments.inventory',
            ]);
        });
    }

    /**
     * Check a resident out: closes the active stay and frees up the bed.
     */
    public static function checkout(ResidentStay $stay, ?string $checkOutDate = null): ResidentStay
    {
        return DB::transaction(function () use ($stay, $checkOutDate) {
            $stay->update([
                'status' => 'ended',
                'actual_check_out_date' => $checkOutDate ?? now()->toDateString(),
            ]);

            $bed = Bed::find($stay->bed_id);
            if ($bed) {
                $bed->update(['status' => 'vacant', 'resident_id' => null]);
            }

            $room = Room::find($stay->room_id);
            if ($room) {
                $room->decrement('occupied_beds');
                $room->refresh();
                $room->update([
                    'status' => $room->occupied_beds <= 0 ? 'available' : 'partially_occupied',
                ]);
            }

            Building::where('id', $stay->building_id)->decrement('occupied');

            return $stay;
        });
    }

    public static function reviewCheckout(
        ResidentStay $stay,
        array $data,
        ?int $reviewedBy
    ): ResidentStay {
        return DB::transaction(function () use ($stay, $data, $reviewedBy) {
            $stay = ResidentStay::query()
                ->with([
                    'resident',
                    'bed',
                    'room',
                    'inventoryAssignments.inventory',
                ])
                ->lockForUpdate()
                ->findOrFail($stay->id);

            if (
                $stay->status !== 'active' ||
                !$stay->check_in_status
            ) {
                throw new \RuntimeException(
                    'Only a checked-in resident can be checked out.'
                );
            }

            $decision = $data['decision'];

            $submittedReturns = collect(
                $data['inventory_returns'] ?? []
            )->keyBy('assignment_id');

            foreach ($stay->inventoryAssignments as $assignment) {
                $review = $submittedReturns->get(
                    $assignment->id
                );

                if (!$review) {
                    if ($decision === 'approved') {
                        throw ValidationException::withMessages([
                            'inventory_returns' =>
                                "Return details are missing for {$assignment->inventory->item_name}.",
                        ]);
                    }

                    continue;
                }

                $good = (int) (
                    $review['returned_good_quantity']
                    ?? 0
                );

                $damaged = (int) (
                    $review['returned_damaged_quantity']
                    ?? 0
                );

                $missing = (int) (
                    $review['missing_quantity']
                    ?? 0
                );

                $reviewedQuantity =
                    $good + $damaged + $missing;

                if (
                    $reviewedQuantity >
                    (int) $assignment->quantity
                ) {
                    throw ValidationException::withMessages([
                        'inventory_returns' =>
                            "Reviewed quantity for {$assignment->inventory->item_name} exceeds the assigned quantity.",
                    ]);
                }

                if (
                    $decision === 'approved' &&
                    $reviewedQuantity !==
                    (int) $assignment->quantity
                ) {
                    throw ValidationException::withMessages([
                        'inventory_returns' =>
                            "Review all assigned quantities for {$assignment->inventory->item_name}.",
                    ]);
                }

                if (
                    ($damaged > 0 || $missing > 0) &&
                    blank($review['return_notes'] ?? null)
                ) {
                    throw ValidationException::withMessages([
                        'inventory_returns' =>
                            "Notes are required for damaged or missing {$assignment->inventory->item_name}.",
                    ]);
                }

                /*
                 * For hold/reject, save the review without
                 * changing stock quantities.
                 */
                $assignment->update([
                    'returned_good_quantity' => $good,
                    'returned_damaged_quantity' => $damaged,
                    'missing_quantity' => $missing,
                    'returned_quantity' =>
                        $good + $damaged,
                    'return_notes' =>
                        $review['return_notes'] ?? null,
                    'return_review_status' =>
                        $decision === 'approved'
                        ? 'approved'
                        : $decision,
                ]);
            }

            $stay->update([
                'checkout_status' => $decision,
                'checkout_notes' =>
                    $data['checkout_notes'] ?? null,
                'checkout_reviewed_by' => $reviewedBy,
                'checkout_reviewed_at' => now(),
            ]);

            /*
             * Hold/reject does not return inventory,
             * close stay or release the room.
             */
            if ($decision !== 'approved') {
                return $stay->fresh([
                    'inventoryAssignments.inventory',
                ]);
            }

            /*
             * Apply inventory return quantities only
             * after checkout approval.
             */
            foreach ($stay->inventoryAssignments as $assignment) {
                $assignment->refresh();

                $inventory = Inventory::query()
                    ->lockForUpdate()
                    ->findOrFail($assignment->inventory_id);

                $good = (int) 
                    $assignment->returned_good_quantity;

                $damaged = (int) 
                    $assignment->returned_damaged_quantity;

                $missing = (int) 
                    $assignment->missing_quantity;

                $totalIssued = (int) $assignment->quantity;

                $inventory->in_use = max(
                    0,
                    (int) $inventory->in_use - $totalIssued
                );

                $inventory->damaged =
                    (int) $inventory->damaged + $damaged;

                $inventory->missing =
                    (int) $inventory->missing + $missing;

                $inventory->available = max(
                    0,
                    (int) $inventory->total_quantity
                    - (int) $inventory->in_use
                    - (int) $inventory->damaged
                    - (int) $inventory->missing
                );

                $inventory->save();

                $assignment->update([
                    'is_returned' => true,
                    'returned_at' => now(),
                    'returned_by' => $reviewedBy,
                    'condition_at_return' =>
                        $missing > 0
                        ? 'missing'
                        : ($damaged > 0
                            ? 'damaged'
                            : 'good'),
                    'return_review_status' => 'approved',
                ]);
            }

            /*
             * Existing final checkout method recalculates
             * daily billing and releases room/bed.
             */
            return self::checkout(
                $stay,
                $data['actual_check_out_date']
            );
        });
    }

    public static function transferRoom(
        ResidentStay $currentStay,
        array $data
    ): ResidentStay {
        return DB::transaction(function () use ($currentStay, $data) {
            $currentStay = ResidentStay::query()
                ->with([
                    'resident',
                    'room',
                    'bed',
                    'inventoryAssignments',
                ])
                ->lockForUpdate()
                ->findOrFail($currentStay->id);

            if (
                !in_array(
                    $currentStay->status,
                    ['active', 'upcoming'],
                    true
                )
            ) {
                throw new \RuntimeException(
                    'Only an active or upcoming stay can be transferred.'
                );
            }

            $effectiveFrom = Carbon::parse(
                $data['effective_from']
            )->startOfDay();

            if (
                $effectiveFrom->lt(
                    $currentStay->check_in_date
                        ->copy()
                        ->startOfDay()
                )
            ) {
                throw new \RuntimeException(
                    'Transfer date cannot be before the current stay check-in date.'
                );
            }

            $newBed = Bed::query()
                ->lockForUpdate()
                ->findOrFail($data['bed_id']);

            if (
                (int) $newBed->id ===
                (int) $currentStay->bed_id
            ) {
                throw new \RuntimeException(
                    'The requested bed is already assigned to this resident.'
                );
            }

            if (
                $newBed->status === 'occupied' ||
                $newBed->resident_id
            ) {
                throw new \RuntimeException(
                    'The requested bed is no longer available.'
                );
            }

            $newRoom = Room::query()
                ->lockForUpdate()
                ->findOrFail($data['room_id']);

            if (
                (int) $newBed->room_id !==
                (int) $newRoom->id
            ) {
                throw new \RuntimeException(
                    'The selected bed does not belong to the selected room.'
                );
            }

            if (
                (int) $newRoom->building_id !==
                (int) $data['building_id'] ||
                (int) $newRoom->floor_id !==
                (int) $data['floor_id']
            ) {
                throw new \RuntimeException(
                    'The selected room does not belong to the selected building and floor.'
                );
            }

            if (
                (int) $newRoom->occupied_beds >=
                (int) $newRoom->capacity
            ) {
                throw new \RuntimeException(
                    'The requested room is already full.'
                );
            }

            $oldRoom = Room::query()
                ->lockForUpdate()
                ->find($currentStay->room_id);

            $oldBed = Bed::query()
                ->lockForUpdate()
                ->find($currentStay->bed_id);

            /*
             * Preserve whether physical check-in already happened.
             */
            $wasCheckedIn =
                (bool) $currentStay->check_in_status;

            $newStatus = $wasCheckedIn
                ? 'active'
                : 'upcoming';

            $billingBasis =
                $data['billing_basis'] ?? 'monthly';

            $rentAmount =
                $billingBasis === 'monthly'
                ? (float) (
                    $data['rent_amount']
                    ?? $newRoom->monthly_rent_per_bed
                )
                : 0;

            $dailyRate =
                $billingBasis === 'daily'
                ? (float) ($data['daily_rate'] ?? 0)
                : null;

            /*
             * Close old stay as transfer, not checkout.
             */
            $currentStay->update([
                'status' => 'transferred',
                'actual_check_out_date' =>
                    $effectiveFrom->toDateString(),
                'checkout_status' => 'not_requested',
                'checkout_notes' =>
                    'Transferred internally to another room.',
            ]);

            /*
             * Release old room and bed.
             */
            if ($oldBed) {
                $oldBed->update([
                    'status' => 'vacant',
                    'resident_id' => null,
                ]);
            }

            if ($oldRoom) {
                $newOldRoomOccupancy = max(
                    0,
                    (int) $oldRoom->occupied_beds - 1
                );

                $oldRoom->update([
                    'occupied_beds' => $newOldRoomOccupancy,
                    'status' =>
                        $newOldRoomOccupancy <= 0
                        ? 'available'
                        : (
                            $newOldRoomOccupancy >=
                            (int) $oldRoom->capacity
                            ? 'occupied'
                            : 'partially_occupied'
                        ),
                ]);
            }

            Building::query()
                ->whereKey($currentStay->building_id)
                ->where('occupied', '>', 0)
                ->decrement('occupied');

            /*
             * Create the new room-specific stay.
             */
            $newStay = ResidentStay::create([
                'resident_id' =>
                    $currentStay->resident_id,

                'building_id' =>
                    $data['building_id'],

                'floor_id' =>
                    $data['floor_id'],

                'room_id' =>
                    $newRoom->id,

                'bed_id' =>
                    $newBed->id,

                'check_in_date' =>
                    $effectiveFrom->toDateString(),

                'check_in_status' =>
                    $wasCheckedIn,

                'checked_in_at' =>
                    $wasCheckedIn
                    ? ($currentStay->checked_in_at ?? now())
                    : null,

                'checked_in_by' =>
                    $wasCheckedIn
                    ? $currentStay->checked_in_by
                    : null,

                'expected_check_out_date' =>
                    $data['expected_check_out_date']
                    ?? $currentStay
                        ->expected_check_out_date,

                'actual_check_out_date' => null,

                'rent_amount' =>
                    $rentAmount,

                'deposit_amount' => $currentStay->deposit_amount,
                'deposit_transferred_from_stay_id' => $currentStay->id,

                'bill_type' =>
                    $billingBasis,

                'status' =>
                    $newStatus,

                'notes' =>
                    $data['notes']
                    ?? "Transferred from stay #{$currentStay->id}",

                'billing_basis' =>
                    $billingBasis,

                'daily_rate' =>
                    $dailyRate,

                'checkout_status' =>
                    'not_requested',
            ]);

            /*
             * Move active student inventory assignment ownership
             * to the new stay. No stock quantity changes occur.
             */
            ResidentInventoryAssignment::query()
                ->where(
                    'resident_stay_id',
                    $currentStay->id
                )
                ->where('is_returned', false)
                ->update([
                    'resident_stay_id' => $newStay->id,
                ]);

            /*
             * Occupy new bed and update counters.
             */
            $newBed->update([
                'status' => 'occupied',
                'resident_id' =>
                    $currentStay->resident_id,
            ]);

            $newRoomOccupancy =
                (int) $newRoom->occupied_beds + 1;

            $newRoom->update([
                'occupied_beds' =>
                    $newRoomOccupancy,

                'status' =>
                    $newRoomOccupancy >=
                    (int) $newRoom->capacity
                    ? 'occupied'
                    : 'partially_occupied',
            ]);

            Building::query()
                ->whereKey($data['building_id'])
                ->increment('occupied');

            /*
             * Resident remains active when already checked in.
             */
            $currentStay->resident?->update([
                'status' =>
                    $wasCheckedIn
                    ? 'active'
                    : 'upcoming',
            ]);

            /*
             * Daily billing is room/stay-date based, so create a
             * new short-stay invoice only for the new stay.
             */
            if (
                $wasCheckedIn &&
                $billingBasis === 'daily'
            ) {
                app(ShortStayBillingService::class)
                    ->createOrUpdateInvoice($newStay);
            }

            return $newStay->fresh([
                'resident',
                'building',
                'floor',
                'room',
                'bed',
                'inventoryAssignments.inventory',
            ]);
        });
    }
}

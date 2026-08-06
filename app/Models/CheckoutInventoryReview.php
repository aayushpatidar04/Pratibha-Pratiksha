<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutInventoryReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkout_request_id',
        'resident_inventory_assignment_id',
        'inventory_id',
        'assigned_quantity',
        'returned_good_quantity',
        'returned_damaged_quantity',
        'missing_quantity',
        'condition_at_review',
        'review_notes',
        'damage_charge',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_quantity' =>
                'integer',

            'returned_good_quantity' =>
                'integer',

            'returned_damaged_quantity' =>
                'integer',

            'missing_quantity' =>
                'integer',

            'damage_charge' =>
                'decimal:2',

            'reviewed_at' =>
                'datetime',
        ];
    }

    public function checkoutRequest(): BelongsTo
    {
        return $this->belongsTo(
            CheckoutRequest::class,
            'checkout_request_id'
        );
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(
            ResidentInventoryAssignment::class,
            'resident_inventory_assignment_id'
        );
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(
            Inventory::class,
            'inventory_id'
        );
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by'
        );
    }

    public function reviewedQuantity(): int
    {
        return
            (int) $this->returned_good_quantity
            + (int) $this->returned_damaged_quantity
            + (int) $this->missing_quantity;
    }

    public function isFullyReviewed(): bool
    {
        return $this->reviewedQuantity()
            === (int) $this->assigned_quantity;
    }

    public function hasQuantityMismatch(): bool
    {
        return $this->reviewedQuantity()
            !== (int) $this->assigned_quantity;
    }
}
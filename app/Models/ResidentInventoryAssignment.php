<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResidentInventoryAssignment extends Model
{
    protected $fillable = [
        'resident_id',
        'resident_stay_id',
        'inventory_id',
        'quantity',
        'condition_at_issue',
        'issue_notes',
        'assigned_at',
        'assigned_by',
        'is_returned',
        'returned_quantity',
        'condition_at_return',
        'return_notes',
        'returned_at',
        'returned_by',
        'returned_good_quantity',
        'returned_damaged_quantity',
        'missing_quantity',
        'return_review_status',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'returned_at' => 'datetime',
            'is_returned' => 'boolean',
            'quantity' => 'integer',
            'returned_quantity' => 'integer',
            'returned_good_quantity' => 'integer',
            'returned_damaged_quantity' => 'integer',
            'missing_quantity' => 'integer',
        ];
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function stay()
    {
        return $this->belongsTo(
            ResidentStay::class,
            'resident_stay_id'
        );
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function assignedByUser()
    {
        return $this->belongsTo(
            User::class,
            'assigned_by'
        );
    }

    public function returnedByUser()
    {
        return $this->belongsTo(
            User::class,
            'returned_by'
        );
    }

    public function getOutstandingQuantityAttribute(): int
    {
        return max(
            0,
            (int) $this->quantity
            - (int) $this->returned_good_quantity
            - (int) $this->returned_damaged_quantity
            - (int) $this->missing_quantity
        );
    }

    public function checkoutReviews(): HasMany
    {
        return $this->hasMany(
            CheckoutInventoryReview::class,
            'resident_inventory_assignment_id'
        );
    }
}
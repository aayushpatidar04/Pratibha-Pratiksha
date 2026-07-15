<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ResidentStay extends Model
{
    use HasFactory;

    protected $table = 'resident_stays';

    protected $fillable = [
        'resident_id',
        'building_id',
        'floor_id',
        'room_id',
        'bed_id',
        'check_in_date',
        'check_in_status',
        'checked_in_at',
        'checked_in_by',
        'expected_check_out_date',
        'actual_check_out_date',
        'checkout_status',
        'checkout_notes',
        'checkout_reviewed_by',
        'checkout_reviewed_at',
        'rent_amount',
        'deposit_amount',
        'bill_type',
        'billing_basis',
        'daily_rate',
        'short_stay_invoice_id',
        'status',
        'notes',
        'deposit_transferred_from_stay_id',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date:Y-m-d',
            'expected_check_out_date' => 'date:Y-m-d',
            'actual_check_out_date' => 'date:Y-m-d',
            'daily_rate' => 'decimal:2',
            'check_in_status' => 'boolean',
            'checked_in_at' => 'datetime',
            'checkout_reviewed_at' => 'datetime',
            'rent_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
        ];
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function shortStayInvoice()
    {
        return $this->belongsTo(
            FeeInvoice::class,
            'short_stay_invoice_id'
        );
    }

    public function checkedInByUser()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function inventoryAssignments()
    {
        return $this->hasMany(
            ResidentInventoryAssignment::class,
            'resident_stay_id'
        );
    }

    public function checkoutReviewedBy()
    {
        return $this->belongsTo(User::class, 'checkout_reviewed_by');
    }
}
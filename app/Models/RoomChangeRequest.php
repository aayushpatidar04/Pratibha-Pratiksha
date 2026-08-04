<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomChangeRequest extends Model
{
    use HasFactory;

    protected $table = 'room_change_requests';

    protected $fillable = [
        'resident_id',
        'current_stay_id',
        'reason',

        'requested_building_id',
        'requested_floor_id',
        'requested_room_id',
        'requested_bed_id',

        'status',
        'request_source',
        'requested_by',
        'requested_by_resident_id',

        'reviewed_by',
        'reviewed_at',
        'cancelled_at',
        'admin_notes',

        'effective_from',
        'new_billing_basis',
        'new_rent_amount',
        'new_daily_rate',
        'new_expected_check_out_date',
        'new_stay_id',
    ];

    protected $appends = [
        'status_label',
        'can_cancel',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date:Y-m-d',

            'new_expected_check_out_date' =>
                'date:Y-m-d',

            'new_rent_amount' => 'decimal:2',
            'new_daily_rate' => 'decimal:2',

            'reviewed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );
    }

    public function currentStay(): BelongsTo
    {
        return $this->belongsTo(
            ResidentStay::class,
            'current_stay_id'
        );
    }

    public function requestedBuilding(): BelongsTo
    {
        return $this->belongsTo(
            Building::class,
            'requested_building_id'
        );
    }

    public function requestedFloor(): BelongsTo
    {
        return $this->belongsTo(
            Floor::class,
            'requested_floor_id'
        );
    }

    public function requestedRoom(): BelongsTo
    {
        return $this->belongsTo(
            Room::class,
            'requested_room_id'
        );
    }

    public function requestedBed(): BelongsTo
    {
        return $this->belongsTo(
            Bed::class,
            'requested_bed_id'
        );
    }

    public function newStay(): BelongsTo
    {
        return $this->belongsTo(
            ResidentStay::class,
            'new_stay_id'
        );
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }

    public function requestedByResident(): BelongsTo
    {
        return $this->belongsTo(
            Resident::class,
            'requested_by_resident_id'
        );
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by'
        );
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => str($this->status)
                ->replace('_', ' ')
                ->title()
                ->toString(),
        };
    }

    public function getCanCancelAttribute(): bool
    {
        return $this->status === 'pending';
    }
}
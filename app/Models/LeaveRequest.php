<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class LeaveRequest extends Model
{
    use HasFactory;

    protected $table = 'leaves';

    protected $fillable = [
        'resident_id',
        'leave_type',
        'from_date',
        'to_date',
        'reason',
        'destination',
        'parent_approval_status',
        'parent_approval_token',
        'parent_approval_sent_at',
        'parent_responded_at',
        'parent_remarks',
        'admin_approval_status',
        'admin_remarks',
        'final_status',
        'gate_pass_code',
        'approved_by',
        'approved_at',
        'cancelled_at',
        'cancelled_by_resident_id',
    ];

    protected $appends = [
        'total_days',
        'can_cancel',
        'leave_type_label',
        'final_status_label',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date:Y-m-d',
            'to_date' => 'date:Y-m-d',
            'parent_approval_sent_at' => 'datetime',
            'parent_responded_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function cancelledByResident(): BelongsTo
    {
        return $this->belongsTo(
            Resident::class,
            'cancelled_by_resident_id'
        );
    }

    public function getTotalDaysAttribute(): int
    {
        if (!$this->from_date || !$this->to_date) {
            return 0;
        }

        return $this->from_date
            ->copy()
            ->startOfDay()
            ->diffInDays(
                $this->to_date->copy()->startOfDay()
            ) + 1;
    }

    public function getCanCancelAttribute(): bool
    {
        if (
            !in_array(
                $this->final_status,
                [
                    'pending',
                    'parent_approval_pending',
                    'approved',
                ],
                true
            )
        ) {
            return false;
        }

        if (!$this->from_date) {
            return false;
        }

        return now()
            ->startOfDay()
            ->lt($this->from_date->copy()->startOfDay());
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return match ($this->leave_type) {
            'home_leave' => 'Home Leave',
            'medical_leave' => 'Medical Leave',
            'emergency_leave' => 'Emergency Leave',
            'day_out' => 'Day Out',
            'night_pass' => 'Night Pass',
            default => str($this->leave_type)
                ->replace('_', ' ')
                ->title()
                ->toString(),
        };
    }

    public function getFinalStatusLabelAttribute(): string
    {
        return match ($this->final_status) {
            'parent_approval_pending' =>
                'Waiting for Parent Approval',

            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
            default => 'Pending',
        };
    }

    public function markParentApproved(
        ?string $remarks = null
    ): void {
        $this->forceFill([
            'parent_approval_status' => 'approved',
            'parent_responded_at' => now(),
            'parent_remarks' => $remarks,

            /*
             * Business rule:
             * Parent approval automatically approves admin side.
             */
            'admin_approval_status' => 'approved',
            'final_status' => 'approved',

            'approved_at' => now(),

            'gate_pass_code' =>
                $this->gate_pass_code
                ?: static::generateGatePassCode(),
        ])->save();
    }

    public function markParentRejected(
        ?string $remarks = null
    ): void {
        $this->forceFill([
            'parent_approval_status' => 'rejected',
            'parent_responded_at' => now(),
            'parent_remarks' => $remarks,
            'final_status' => 'rejected',
        ])->save();
    }

    public static function generateGatePassCode(): string
    {
        do {
            $code = 'GP-'
                . now()->format('ymd')
                . '-'
                . strtoupper(
                    str()->random(6)
                );
        } while (
            static::where(
                'gate_pass_code',
                $code
            )->exists()
        );

        return $code;
    }
}

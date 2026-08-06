<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckoutRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_UNDER_ADMIN_REVIEW =
        'under_admin_review';

    public const STATUS_ASSIGNED_TO_WARDEN =
        'assigned_to_warden';

    public const STATUS_WARDEN_REVIEW_IN_PROGRESS =
        'warden_review_in_progress';

    public const STATUS_WARDEN_APPROVED =
        'warden_approved';

    public const STATUS_WARDEN_REJECTED =
        'warden_rejected';

    public const STATUS_ADMIN_APPROVED =
        'admin_approved';

    public const STATUS_ADMIN_REJECTED =
        'admin_rejected';

    public const STATUS_ON_HOLD =
        'on_hold';

    public const STATUS_READY_FOR_EXIT =
        'ready_for_exit';

    public const STATUS_COMPLETED =
        'completed';

    public const STATUS_CANCELLED =
        'cancelled';

    public const STATUS_EXPIRED =
        'expired';

    protected $fillable = [
        'resident_id',
        'resident_stay_id',

        'requested_by_type',
        'requested_by_id',

        'requested_checkout_date',
        'requested_at',

        'required_notice_days',
        'actual_notice_days',
        'is_short_notice',
        'short_notice_warning_accepted',
        'warning_accepted_at',

        'short_notice_charge',
        'short_notice_charge_notes',

        'reason',
        'resident_notes',

        'status',

        'admin_review_status',
        'admin_reviewed_by',
        'admin_reviewed_at',
        'admin_review_notes',

        'assigned_warden_id',
        'warden_assigned_by',
        'warden_assigned_at',
        'warden_review_status',
        'warden_reviewed_at',
        'warden_review_notes',

        'final_approved_by',
        'final_approved_at',
        'final_approval_notes',

        'dues_clearance_status',
        'outstanding_amount_at_request',
        'outstanding_dues_deduction',
        'short_notice_charge_final',
        'asset_damage_charge',
        'other_checkout_charge',
        'charge_notes',

        'exit_token',
        'exit_token_generated_at',
        'exit_token_expires_at',
        'exit_token_generated_by',

        'gate_verified_by',
        'gate_verified_at',
        'gate_verification_notes',

        'actual_checkout_at',
        'completed_by',
        'completion_notes',

        'cancelled_by_type',
        'cancelled_by_id',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'requested_checkout_date' =>
                'date:Y-m-d',

            'requested_at' =>
                'datetime',

            'required_notice_days' =>
                'integer',

            'actual_notice_days' =>
                'integer',

            'is_short_notice' =>
                'boolean',

            'short_notice_warning_accepted' =>
                'boolean',

            'warning_accepted_at' =>
                'datetime',

            'short_notice_charge' =>
                'decimal:2',

            'admin_reviewed_at' =>
                'datetime',

            'warden_assigned_at' =>
                'datetime',

            'warden_reviewed_at' =>
                'datetime',

            'final_approved_at' =>
                'datetime',

            'outstanding_amount_at_request' =>
                'decimal:2',

            'outstanding_dues_deduction' => 'decimal:2',

            'short_notice_charge_final' =>
                'decimal:2',

            'asset_damage_charge' =>
                'decimal:2',

            'other_checkout_charge' =>
                'decimal:2',

            'exit_token_generated_at' =>
                'datetime',

            'exit_token_expires_at' =>
                'datetime',

            'gate_verified_at' =>
                'datetime',

            'actual_checkout_at' =>
                'datetime',

            'cancelled_at' =>
                'datetime',
        ];
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(
            ResidentStay::class,
            'resident_stay_id'
        );
    }

    public function adminReviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'admin_reviewed_by'
        );
    }

    public function assignedWarden(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_warden_id'
        );
    }

    public function wardenAssignedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'warden_assigned_by'
        );
    }

    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'final_approved_by'
        );
    }

    public function exitTokenGeneratedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'exit_token_generated_by'
        );
    }

    public function gateVerifier(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'gate_verified_by'
        );
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'completed_by'
        );
    }

    public function inventoryReviews(): HasMany
    {
        return $this->hasMany(
            CheckoutInventoryReview::class,
            'checkout_request_id'
        );
    }

    public function histories(): HasMany
    {
        return $this->hasMany(
            CheckoutRequestHistory::class,
            'checkout_request_id'
        );
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->whereNotIn(
            'status',
            [
                self::STATUS_COMPLETED,
                self::STATUS_CANCELLED,
                self::STATUS_ADMIN_REJECTED,
                self::STATUS_WARDEN_REJECTED,
                self::STATUS_EXPIRED,
            ]
        );
    }

    public function scopeForResident(
        Builder $query,
        Resident|int $resident
    ): Builder {
        $residentId = $resident instanceof Resident
            ? $resident->id
            : $resident;

        return $query->where(
            'resident_id',
            $residentId
        );
    }

    public function canBeCancelledByResident(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_PENDING,
                self::STATUS_UNDER_ADMIN_REVIEW,
            ],
            true
        );
    }

    public function hasWardenCompletedReview(): bool
    {
        return $this->warden_review_status
            === 'approved';
    }

    public function isReadyForFinalApproval(): bool
    {
        return $this->status
            === self::STATUS_WARDEN_APPROVED;
    }

    public function isReadyForExit(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_ADMIN_APPROVED,
                self::STATUS_READY_FOR_EXIT,
            ],
            true
        );
    }

    public function totalCheckoutCharges(): float
    {
        return round(
            (float) $this->short_notice_charge_final
            + (float) $this->asset_damage_charge
            + (float) $this->other_checkout_charge,
            2
        );
    }

    public function totalCheckoutDeductions(): float
    {
        return round(
            (float) $this->short_notice_charge_final
            + (float) $this->asset_damage_charge
            + (float) $this->other_checkout_charge
            + (float) $this->outstanding_dues_deduction,
            2
        );
    }

    public function refundableSecurityDeposit(
        float $securityDeposit
    ): float {
        return max(
            0,
            round(
                $securityDeposit
                - $this->totalCheckoutDeductions(),
                2
            )
        );
    }

    public function securityDepositInvoice(): BelongsTo
    {
        return $this->belongsTo(
            FeeInvoice::class,
            'resident_stay_id',
            'stay_id'
        )
            ->where('resident_id', $this->resident_id)
            ->where('fee_type', 'security-deposit');
    }
}
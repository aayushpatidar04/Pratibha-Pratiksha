<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;


class FeeInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fee_invoices';

    protected $fillable = [
        'resident_id',
        'stay_id',
        'application_id',
        'invoice_number',
        'fee_type',
        'amount',
        'due_date',
        'paid_amount',
        'late_fee_amount',
        'late_fee_per_day',
        'late_fee_waived',
        'late_fee_frozen_at',
        'waived_by',
        'waived_at',
        'waive_reason',
        'status',
        'description',
        'monthly_config_id'
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'waived_at' => 'datetime',
            'late_fee_waived' => 'boolean',
            'late_fee_amount' => 'decimal:2',
            'late_fee_per_day' => 'decimal:2',
            'late_fee_frozen_at' => 'date:Y-m-d',
            'paid_amount' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    protected $appends = [
        'core_amount',
        'paid_before_due_date',
        'total_payable',
        'core_balance',
        'late_fee_balance',
        'balance_due',
        'computed_status',
        'is_overdue',
        'effective_late_fee_amount',
        'days_late',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeeInvoice $invoice) {
            if (blank($invoice->resident_id) && blank($invoice->application_id)) {
                throw ValidationException::withMessages([
                    'resident_id' => 'Either resident or application must be associated with the invoice.',
                ]);
            }
        });

        static::updating(function (FeeInvoice $invoice) {
            $residentId = $invoice->resident_id;
            $applicationId = $invoice->application_id;

            if ($invoice->isDirty('resident_id')) {
                $residentId = $invoice->getAttribute('resident_id');
            }

            if ($invoice->isDirty('application_id')) {
                $applicationId = $invoice->getAttribute('application_id');
            }

            if (blank($residentId) && blank($applicationId)) {
                throw ValidationException::withMessages([
                    'resident_id' => 'Either resident or application must be associated with the invoice.',
                ]);
            }
        });
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function application()
    {
        return $this->belongsTo(RegistrationApplication::class, 'application_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    public function items()
    {
        return $this->hasMany(FeeInvoiceItem::class, 'invoice_id');
    }

    public function stay()
    {
        return $this->belongsTo(ResidentStay::class, 'stay_id');
    }

    public function monthlyConfig()
    {
        return $this->belongsTo(MonthlyBillingConfig::class, 'monthly_config_id');
    }

    public function waivedByUser()
    {
        return $this->belongsTo(User::class, 'waived_by');
    }

    // Core amount (without late fee)
    public function getCoreAmountAttribute(): float
    {
        return (float) $this->amount;
    }

    public function getPaidBeforeDueDateAttribute(): float
    {
        if (!$this->due_date) {
            return 0.0;
        }

        if ($this->relationLoaded('payments')) {
            return (float) $this->payments
                ->filter(
                    fn($payment) =>
                    $payment->payment_date
                    && $payment->payment_date->lte($this->due_date)
                )
                ->sum('amount');
        }

        return (float) $this->payments()
            ->whereDate('payment_date', '<=', $this->due_date->toDateString())
            ->sum('amount');
    }

    /**
     * Whether this invoice uses per-day accrual instead of the
     * legacy flat late fee.
     */
    public function usesDailyLateFee(): bool
    {
        return (float) ($this->late_fee_per_day ?? 0) > 0;
    }

    /**
     * The late fee amount as of a given date.
     *
     * - Per-day invoices: (days between due_date and $date) × rate.
     * - Legacy flat invoices: the stored flat late_fee_amount, unchanged.
     */
    public function lateFeeAmountOn(CarbonInterface $date): float
    {
        if (!$this->due_date) {
            return 0.0;
        }

        if (!$this->usesDailyLateFee()) {
            return (float) $this->getRawOriginal('late_fee_amount');
        }

        $dueDate = $this->due_date->copy()->startOfDay();
        $asOf = $date->copy()->startOfDay();

        if (!$asOf->gt($dueDate)) {
            return 0.0;
        }

        $daysLate = $dueDate->diffInDays($asOf);

        return round($daysLate * (float) $this->late_fee_per_day, 2);
    }

    /**
     * The date "as of" which the current/live late fee should be evaluated.
     * Once an invoice has been fully paid off, this freezes at the date
     * that happened, so a paid invoice never "reopens" just because more
     * days went by.
     */
    public function getLateFeeEvaluationDateAttribute(): CarbonInterface
    {
        if ($this->late_fee_frozen_at) {
            return Carbon::parse($this->late_fee_frozen_at)->startOfDay();
        }

        return now()->startOfDay();
    }

    /**
     * Number of days late as of the evaluation date (0 if not overdue,
     * or if late fee is waived). Handy for UI ("12 days × ₹50/day").
     */
    public function getDaysLateAttribute(): int
    {
        if (!$this->due_date || !$this->usesDailyLateFee() || $this->late_fee_waived) {
            return 0;
        }

        $dueDate = $this->due_date->copy()->startOfDay();
        $asOf = $this->late_fee_evaluation_date;

        return $asOf->gt($dueDate) ? $dueDate->diffInDays($asOf) : 0;
    }

    /**
     * The late fee amount that should actually be shown/used right now,
     * accounting for the freeze point above. Controllers should copy this
     * onto `late_fee_amount` before sending invoices to the frontend.
     */
    public function getEffectiveLateFeeAmountAttribute(): float
    {
        if ($this->late_fee_waived) {
            return 0.0;
        }

        return $this->lateFeeAmountOn($this->late_fee_evaluation_date);
    }

    // Total payable including late fee (if applicable)
    public function getTotalPayableAttribute(): float
    {
        $lateFeeAmount = $this->effective_late_fee_amount;

        $lateFeeWasPaid = (
            $lateFeeAmount > 0
            && (float) $this->paid_amount >= (
                $this->core_amount + $lateFeeAmount
            )
        );

        $lateFeeApplies = (
            !$this->late_fee_waived
            && (
                $this->is_overdue
                || $lateFeeWasPaid
            )
        );

        return $this->core_amount
            + ($lateFeeApplies ? $lateFeeAmount : 0.0);
    }

    // Balance due on core amount
    public function getCoreBalanceAttribute(): float
    {
        return max(0, $this->core_amount - $this->paid_amount);
    }

    // Late fee balance (only if core is fully paid)
    public function getLateFeeBalanceAttribute(): float
    {
        if (!$this->is_overdue || $this->late_fee_waived) {
            return 0.0;
        }

        if ($this->core_balance > 0) {
            return 0.0;
        }

        $amountPaidAboveCore = max(
            0,
            (float) $this->paid_amount - $this->core_amount
        );

        return max(
            0,
            $this->effective_late_fee_amount - $amountPaidAboveCore
        );
    }

    // Total balance due
    public function getBalanceDueAttribute(): float
    {
        return max(
            0,
            $this->total_payable - (float) $this->paid_amount
        );
    }

    // Status logic
    public function getComputedStatusAttribute(): string
    {
        if ($this->paid_amount >= $this->total_payable) {
            return 'paid';
        }

        if ($this->core_balance <= 0 && $this->late_fee_balance > 0) {
            return 'late_fee_pending';
        }

        if ($this->paid_amount > 0) {
            return 'partial';
        }

        if ($this->due_date < now()->startOfDay()) {
            return 'overdue';
        }

        return 'pending';
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        if (!now()->startOfDay()->gt($this->due_date->copy()->startOfDay())) {
            return false;
        }

        if ($this->paid_before_due_date >= $this->core_amount) {
            return false;
        }

        $lateFee = $this->late_fee_waived
            ? 0
            : $this->effective_late_fee_amount;

        return (float) $this->paid_amount < ($this->core_amount + $lateFee);
    }

    public function wasCorePaidBy(CarbonInterface $date): bool
    {
        $paidByDate = (float) $this->payments()
            ->whereDate('payment_date', '<=', $date->toDateString())
            ->sum('amount');

        return $paidByDate >= $this->core_amount;
    }

    public function isOverdueOn(CarbonInterface $date): bool
    {
        if (!$this->due_date) {
            return false;
        }

        if (
            !$date->copy()->startOfDay()->gt(
                $this->due_date->copy()->startOfDay()
            )
        ) {
            return false;
        }

        return !$this->wasCorePaidBy($this->due_date);
    }

    public function totalPayableOn(CarbonInterface $date): float
    {
        $lateFee = $this->isOverdueOn($date)
            && !$this->late_fee_waived
            ? $this->lateFeeAmountOn($date)
            : 0.0;

        return $this->core_amount + $lateFee;
    }

    public function balanceDueOn(CarbonInterface $date): float
    {
        return max(
            0,
            $this->totalPayableOn($date) - (float) $this->paid_amount
        );
    }

    /**
     * Call after recording a payment. If the invoice is now fully paid off
     * as of the payment date, freezes the late fee so it stops growing.
     */
    public function freezeLateFeeIfSettled(CarbonInterface $paymentDate): void
    {
        if (!$this->usesDailyLateFee() || $this->late_fee_frozen_at) {
            return;
        }

        if ((float) $this->paid_amount >= $this->totalPayableOn($paymentDate)) {
            $this->late_fee_frozen_at = $paymentDate->toDateString();
            $this->save();
        }
    }
}
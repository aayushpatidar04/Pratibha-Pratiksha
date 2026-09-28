<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;


class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'invoice_id',
        'resident_id',
        'application_id',
        'amount',
        'payment_mode',
        'transaction_id',
        'payment_date',
        'notes',
        'receipt_number'
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'payment_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (blank($payment->resident_id) && blank($payment->application_id)) {
                if ($payment->invoice?->fee_type === 'donation') {
                    return;
                }

                throw ValidationException::withMessages([
                    'resident_id' => 'Either resident or application must be associated with the payment.',
                ]);
            }
        });

        static::updating(function (Payment $payment) {
            $residentId = $payment->resident_id;
            $applicationId = $payment->application_id;

            if ($payment->isDirty('resident_id')) {
                $residentId = $payment->getAttribute('resident_id');
            }

            if ($payment->isDirty('application_id')) {
                $applicationId = $payment->getAttribute('application_id');
            }

            if (blank($residentId) && blank($applicationId)) {
                if ($payment->invoice?->fee_type === 'donation') {
                    return;
                }

                throw ValidationException::withMessages([
                    'resident_id' => 'Either resident or application must be associated with the payment.',
                ]);
            }
        });
    }

    public function invoice()
    {
        return $this->belongsTo(FeeInvoice::class, 'invoice_id');
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class, 'resident_id');
    }

    public function proofs()
    {
        return $this->hasMany(PaymentProof::class, 'payment_id');
    }

    public function application()
    {
        return $this->belongsTo(RegistrationApplication::class);
    }
}

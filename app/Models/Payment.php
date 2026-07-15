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
        static::creating(function (Payment $invoice) {
            if (blank($invoice->resident_id) && blank($invoice->application_id)) {
                throw ValidationException::withMessages([
                    'resident_id' => 'Either resident or application must be associated with the invoice.',
                ]);
            }
        });

        static::updating(function (Payment $invoice) {
            $residentId = $invoice->resident_id;
            $applicationId = $invoice->application_id;

            // Handle attributes being changed in this update
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'invoice_id',
        'resident_id',
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
            'payment_date' => 'date'
        ];
    }

    public function invoice() { return $this->belongsTo(FeeInvoice::class, 'invoice_id'); }
}

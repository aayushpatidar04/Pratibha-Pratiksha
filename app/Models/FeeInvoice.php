<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class FeeInvoice extends Model
{
    use HasFactory;

    protected $table = 'fee_invoices';

    protected $fillable = [
        'resident_id',
        'stay_id',
        'invoice_number',
        'fee_type',
        'amount',
        'due_date',
        'paid_amount',
        'status',
        'description'
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date'
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }

    public function payments() { return $this->hasMany(Payment::class, 'invoice_id'); }
}

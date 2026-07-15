<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeInvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'item_type',
        'amenity_type',
        'title',
        'amount',
        'description',
        'is_late_fee',
    ];

    protected $casts = [
        'is_late_fee' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(FeeInvoice::class, 'invoice_id');
    }
}
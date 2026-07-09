<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeInvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'item_type',
        'title',
        'amount',
        'description',
    ];

    public function invoice()
    {
        return $this->belongsTo(FeeInvoice::class, 'invoice_id');
    }
}
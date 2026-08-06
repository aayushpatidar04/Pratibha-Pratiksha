<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutRequestHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkout_request_id',
        'action',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function checkoutRequest(): BelongsTo
    {
        return $this->belongsTo(
            CheckoutRequest::class,
            'checkout_request_id'
        );
    }
}
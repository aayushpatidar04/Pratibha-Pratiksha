<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeUpdate extends Model
{
    protected $fillable = [
        'notice_id',
        'action',
        'old_status',
        'new_status',
        'remarks',
        'updated_by',
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(
            Notice::class,
            'notice_id'
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyAlertUpdate extends Model
{
    protected $fillable = [
        'emergency_alert_id',
        'old_status',
        'new_status',
        'remarks',
        'updated_by',
        'updated_by_resident',
    ];

    protected function casts(): array
    {
        return [
            'updated_by_resident' => 'boolean',
        ];
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(
            EmergencyAlert::class,
            'emergency_alert_id'
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
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmergencyAlert extends Model
{
    use HasFactory;

    protected $table = 'emergency_alerts';

    public $timestamps = true;

    protected $fillable = [
        'resident_id',
        'building_id',
        'room_id',
        'category',
        'description',
        'location',
        'status',
        'assigned_to',
        'acknowledged_by',
        'acknowledged_at',
        'escalation_notes',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $appends = [
        'category_label',
        'status_label',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(
            Building::class,
            'building_id'
        );
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(
            Room::class,
            'room_id'
        );
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_to'
        );
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'acknowledged_by'
        );
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'resolved_by'
        );
    }

    public function updates(): HasMany
    {
        return $this->hasMany(
            EmergencyAlertUpdate::class,
            'emergency_alert_id'
        )->oldest('created_at');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'medical' => 'Medical Emergency',
            'fire' => 'Fire',
            'theft' => 'Theft',
            'stuck_in_lift' => 'Stuck in Lift',
            'need_food' => 'Need Food',
            'disaster' => 'Disaster',
            'domestic_violence' => 'Domestic Violence',
            'threat' => 'Threat',
            'violence' => 'Violence',
            'suicidal' => 'Self-Harm Emergency',
            'mental_depression' => 'Mental Health Emergency',
            'others' => 'Other Emergency',
            default => str($this->category)
                ->replace('_', ' ')
                ->title()
                ->toString(),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'escalated' => 'Escalated',
            'resolved' => 'Resolved',
            default => ucfirst((string) $this->status),
        };
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array(
            $this->status,
            [
                'active',
                'escalated',
            ],
            true
        );
    }
}
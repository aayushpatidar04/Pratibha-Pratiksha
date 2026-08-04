<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    use HasFactory;

    protected $table = 'complaints';

    protected $fillable = [
        'resident_id',
        'building_id',
        'room_id',
        'category',
        'priority',
        'title',
        'description',
        'status',
        'assigned_to',
        'resolution_notes',
        'resolved_at',
        'rating',
    ];

    protected $appends = [
        'category_label',
        'priority_label',
        'status_label',
        'can_delete',
        'can_rate',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'rating' => 'integer',
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

    public function updates(): HasMany
    {
        return $this->hasMany(
            ComplaintUpdate::class,
            'complaint_id'
        )->latest('created_at');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'electrical' => 'Electrical',
            'plumbing' => 'Plumbing',
            'furniture' => 'Furniture',
            'wifi' => 'Wi-Fi / Internet',
            'cleaning' => 'Cleaning',
            'security' => 'Security',
            'food' => 'Food / Mess',
            'other' => 'Other',
            default => str($this->category)
                ->replace('_', ' ')
                ->title()
                ->toString(),
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => ucfirst((string) $this->priority),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'escalated' => 'Escalated',
            'rejected' => 'Rejected',
            default => str($this->status)
                ->replace('_', ' ')
                ->title()
                ->toString(),
        };
    }

    /**
     * Residents can remove only complaints that have not started processing.
     */
    public function getCanDeleteAttribute(): bool
    {
        return $this->status === 'open';
    }

    /**
     * A resolved complaint can be rated only once.
     */
    public function getCanRateAttribute(): bool
    {
        return $this->status === 'resolved'
            && $this->rating === null;
    }
}
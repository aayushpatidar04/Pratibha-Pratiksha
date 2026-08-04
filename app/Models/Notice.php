<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'summary',
        'content',
        'category',
        'priority',
        'status',
        'audience_type',
        'is_pinned',
        'requires_acknowledgement',
        'publish_at',
        'expires_at',
        'published_at',
        'archived_at',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'category_label',
        'priority_label',
        'status_label',
        'is_currently_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'requires_acknowledgement' => 'boolean',
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(
            Building::class,
            'notice_building'
        )->withTimestamps();
    }

    public function residents(): BelongsToMany
    {
        return $this->belongsToMany(
            Resident::class,
            'notice_resident'
        )->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(
            NoticeAttachment::class,
            'notice_id'
        );
    }

    public function reads(): HasMany
    {
        return $this->hasMany(
            NoticeRead::class,
            'notice_id'
        );
    }

    public function updates(): HasMany
    {
        return $this->hasMany(
            NoticeUpdate::class,
            'notice_id'
        )->latest('created_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    public function scopeVisibleToResident(
        Builder $query,
        Resident $resident
    ): Builder {
        $buildingId = $resident->currentStay?->building_id;

        return $query->where(function (
            Builder $query
        ) use (
            $resident,
            $buildingId
        ): void {
            $query->where(
                'audience_type',
                'all'
            );

            $query->orWhere(function (
                Builder $query
            ) use ($buildingId): void {
                $query->where(
                    'audience_type',
                    'buildings'
                );

                if ($buildingId) {
                    $query->whereHas(
                        'buildings',
                        fn (Builder $buildingQuery) =>
                            $buildingQuery->where(
                                'buildings.id',
                                $buildingId
                            )
                    );
                } else {
                    $query->whereRaw('1 = 0');
                }
            });

            $query->orWhere(function (
                Builder $query
            ) use ($resident): void {
                $query
                    ->where(
                        'audience_type',
                        'residents'
                    )
                    ->whereHas(
                        'residents',
                        fn (Builder $residentQuery) =>
                            $residentQuery->where(
                                'residents.id',
                                $resident->id
                            )
                    );
            });
        });
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'general' => 'General',
            'academic' => 'Academic',
            'hostel' => 'Hostel',
            'mess' => 'Mess',
            'maintenance' => 'Maintenance',
            'event' => 'Event',
            'payment' => 'Payment',
            'emergency' => 'Emergency',
            'policy' => 'Policy',
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
            'normal' => 'Normal',
            'important' => 'Important',
            'urgent' => 'Urgent',
            default => ucfirst((string) $this->priority),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'published' => 'Published',
            'expired' => 'Expired',
            'archived' => 'Archived',
            default => ucfirst((string) $this->status),
        };
    }

    public function getIsCurrentlyVisibleAttribute(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if (
            $this->publish_at
            && $this->publish_at->isFuture()
        ) {
            return false;
        }

        if (
            $this->expires_at
            && $this->expires_at->isPast()
        ) {
            return false;
        }

        return true;
    }
}
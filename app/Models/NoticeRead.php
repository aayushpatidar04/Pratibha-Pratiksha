<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeRead extends Model
{
    protected $fillable = [
        'notice_id',
        'resident_id',
        'first_read_at',
        'last_read_at',
        'read_count',
        'acknowledged_at',
        'acknowledgement_ip',
    ];

    protected function casts(): array
    {
        return [
            'first_read_at' => 'datetime',
            'last_read_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'read_count' => 'integer',
        ];
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(
            Notice::class,
            'notice_id'
        );
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );
    }

    public function markRead(): void
    {
        $now = now();

        $this->forceFill([
            'first_read_at' =>
                $this->first_read_at ?: $now,

            'last_read_at' =>
                $now,

            'read_count' =>
                ((int) $this->read_count) + 1,
        ])->save();
    }

    public function acknowledge(
        ?string $ipAddress = null
    ): void {
        if ($this->acknowledged_at) {
            return;
        }

        $this->forceFill([
            'acknowledged_at' => now(),
            'acknowledgement_ip' =>
                $ipAddress,
        ])->save();
    }
}
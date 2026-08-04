<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class NoticeAttachment extends Model
{
    protected $fillable = [
        'notice_id',
        'file_path',
        'original_name',
        'file_type',
        'file_size',
    ];

    protected $appends = [
        'file_url',
        'formatted_size',
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(
            Notice::class,
            'notice_id'
        );
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url(
            $this->file_path
        );
    }

    public function getFormattedSizeAttribute(): string
    {
        $size = (int) ($this->file_size ?? 0);

        if ($size <= 0) {
            return 'Unknown size';
        }

        if ($size < 1024) {
            return "{$size} B";
        }

        if ($size < 1024 * 1024) {
            return round(
                $size / 1024,
                1
            ) . ' KB';
        }

        return round(
            $size / (1024 * 1024),
            1
        ) . ' MB';
    }
}
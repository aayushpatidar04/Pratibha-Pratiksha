<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles';

    public $timestamps = false;

    protected $fillable = [
        'resident_id',
        'vehicle_type',
        'vehicle_number',
        'color',
        'model',
        'rc_file_url',
    ];

    protected $appends = [
        'rc_file_public_url',
        'rc_file_extension',
        'rc_is_image',
        'rc_is_pdf',
    ];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );
    }

    public function getRcFilePublicUrlAttribute(): ?string
    {
        if (!$this->rc_file_url) {
            return null;
        }

        if (
            str_starts_with($this->rc_file_url, 'http://')
            || str_starts_with($this->rc_file_url, 'https://')
            || str_starts_with($this->rc_file_url, '/storage/')
        ) {
            return $this->rc_file_url;
        }

        return Storage::disk('public')->url(
            ltrim($this->rc_file_url, '/')
        );
    }

    public function getRcFileExtensionAttribute(): ?string
    {
        if (!$this->rc_file_url) {
            return null;
        }

        $path = parse_url(
            $this->rc_file_url,
            PHP_URL_PATH
        );

        return strtolower(
            pathinfo(
                $path ?: $this->rc_file_url,
                PATHINFO_EXTENSION
            )
        );
    }

    public function getRcIsImageAttribute(): bool
    {
        return in_array(
            $this->rc_file_extension,
            [
                'jpg',
                'jpeg',
                'png',
                'webp',
            ],
            true
        );
    }

    public function getRcIsPdfAttribute(): bool
    {
        return $this->rc_file_extension === 'pdf';
    }

    public function rcStoragePath(): ?string
    {
        if (!$this->rc_file_url) {
            return null;
        }

        $path = parse_url(
            $this->rc_file_url,
            PHP_URL_PATH
        );

        if (!$path) {
            return null;
        }

        return ltrim(
            str_replace(
                '/storage/',
                '',
                $path
            ),
            '/'
        );
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';

    public $timestamps = false;

    protected $fillable = [
        'resident_id',
        'document_type',
        'file_url',
        'file_name',
        'verification_status',
        'notes',
        'uploaded_at',
    ];

    protected $appends = [
        'document_label',
        'is_image',
        'is_pdf',
        'file_extension',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(
            Resident::class,
            'resident_id'
        );
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(
            KycRequirement::class,
            'document_type',
            'document_type'
        );
    }

    public function getDocumentLabelAttribute(): string
    {
        if (
            $this->document_type === 'other'
            && filled($this->notes)
        ) {
            return $this->notes;
        }

        return $this->requirement?->label
            ?? str($this->document_type)
                ->replace('_', ' ')
                ->title()
                ->toString();
    }

    public function getFileExtensionAttribute(): string
    {
        $path = parse_url(
            (string) $this->file_url,
            PHP_URL_PATH
        );

        return strtolower(
            pathinfo(
                $path ?: (string) $this->file_name,
                PATHINFO_EXTENSION
            )
        );
    }

    public function getIsImageAttribute(): bool
    {
        return in_array(
            $this->file_extension,
            [
                'jpg',
                'jpeg',
                'png',
                'webp',
                'gif',
            ],
            true
        );
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->file_extension === 'pdf';
    }

    public function storagePath(): ?string
    {
        if (!$this->file_url) {
            return null;
        }

        $path = parse_url(
            $this->file_url,
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

    public function fileExists(): bool
    {
        $path = $this->storagePath();

        return $path
            && Storage::disk('public')->exists($path);
    }
}
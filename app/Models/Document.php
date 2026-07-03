<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';

    protected $fillable = [
        'resident_id',
        'document_type',
        'file_url',
        'file_name',
        'verification_status',
        'notes',
        'uploaded_at'
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime'
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }
}

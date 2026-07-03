<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class EmergencyAlert extends Model
{
    use HasFactory;

    protected $table = 'emergency_alerts';

    protected $fillable = [
        'resident_id',
        'category',
        'description',
        'location',
        'status',
        'resolved_by',
        'resolved_at'
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime'
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }
}

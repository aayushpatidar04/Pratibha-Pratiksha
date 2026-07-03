<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


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
        'rating'
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime'
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }
}

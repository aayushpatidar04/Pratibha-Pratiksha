<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Bed extends Model
{
    use HasFactory;

    protected $table = 'beds';

    protected $fillable = [
        'room_id',
        'bed_number',
        'status',
        'resident_id'
    ];

    protected function casts(): array
    {
        return [
            
        ];
    }

    public function room() { return $this->belongsTo(Room::class); }

    public function resident() { return $this->belongsTo(Resident::class); }
}

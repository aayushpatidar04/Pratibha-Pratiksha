<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Floor extends Model
{
    use HasFactory;

    protected $table = 'floors';

    protected $fillable = [
        'building_id',
        'floor_number',
        'name',
        'total_rooms'
    ];

    protected function casts(): array
    {
        return [
            
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }

    public function rooms() { return $this->hasMany(Room::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Building extends Model
{
    use HasFactory;

    protected $table = 'buildings';

    protected $fillable = [
        'name',
        'code',
        'type',
        'address',
        'total_floors',
        'total_rooms',
        'total_capacity',
        'occupied',
        'status'
    ];

    protected function casts(): array
    {
        return [
            
        ];
    }

    public function floors() { return $this->hasMany(Floor::class); }

    public function rooms() { return $this->hasMany(Room::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Room extends Model
{
    use HasFactory;

    protected $table = 'rooms';

    protected $fillable = [
        'building_id',
        'floor_id',
        'room_number',
        'room_type',
        'capacity',
        'occupied_beds',
        'monthly_rent_per_bed',
        'has_ac',
        'has_wifi',
        'has_attached_bath',
        'has_balcony',
        'has_study_table',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'has_ac' => 'boolean',
            'has_wifi' => 'boolean',
            'has_attached_bath' => 'boolean',
            'has_balcony' => 'boolean',
            'has_study_table' => 'boolean',
            'monthly_rent_per_bed' => 'decimal:2'
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }

    public function floor() { return $this->belongsTo(Floor::class); }

    public function beds() { return $this->hasMany(Bed::class); }
}

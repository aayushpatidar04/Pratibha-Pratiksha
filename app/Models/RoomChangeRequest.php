<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomChangeRequest extends Model
{
    use HasFactory;

    protected $table = 'room_change_requests';

    protected $fillable = [
        'resident_id',
        'current_stay_id',
        'reason',
        'requested_building_id',
        'requested_floor_id',
        'requested_room_id',
        'requested_bed_id',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }

    public function currentStay() { return $this->belongsTo(ResidentStay::class, 'current_stay_id'); }

    public function requestedBuilding() { return $this->belongsTo(Building::class, 'requested_building_id'); }

    public function requestedFloor() { return $this->belongsTo(Floor::class, 'requested_floor_id'); }

    public function requestedRoom() { return $this->belongsTo(Room::class, 'requested_room_id'); }

    public function requestedBed() { return $this->belongsTo(Bed::class, 'requested_bed_id'); }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ResidentStay extends Model
{
    use HasFactory;

    protected $table = 'resident_stays';

    protected $fillable = [
        'resident_id',
        'building_id',
        'floor_id',
        'room_id',
        'bed_id',
        'check_in_date',
        'expected_check_out_date',
        'actual_check_out_date',
        'rent_amount',
        'deposit_amount',
        'bill_type',
        'status',
        'notes'
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'expected_check_out_date' => 'date',
            'actual_check_out_date' => 'date'
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }
}

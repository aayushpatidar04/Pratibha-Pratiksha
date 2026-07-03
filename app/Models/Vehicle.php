<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles';

    protected $fillable = [
        'resident_id',
        'vehicle_type',
        'vehicle_number',
        'color',
        'model',
        'rc_file_url'
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }
}

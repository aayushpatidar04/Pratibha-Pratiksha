<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class GatePass extends Model
{
    use HasFactory;

    protected $table = 'gate_passes';

    protected $fillable = [
        'resident_id',
        'pass_type',
        'from_time',
        'to_time',
        'purpose',
        'visitor_name',
        'visitor_phone',
        'visitor_id_proof',
        'status',
        'approved_by'
    ];

    protected function casts(): array
    {
        return [
            'from_time' => 'datetime',
            'to_time' => 'datetime'
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }
}

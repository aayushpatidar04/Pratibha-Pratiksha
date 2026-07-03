<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'resident_id',
        'date',
        'attendance_type',
        'status',
        'marked_by',
        'notes'
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'date' => 'date'
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }
}

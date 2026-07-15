<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DisciplinaryAction extends Model
{
    use HasFactory;

    protected $table = 'disciplinary_actions';

    protected $fillable = [
        'resident_id',
        'incident_date',
        'incident_time',
        'description',
        'witnesses',
        'warning_level',
        'action_taken',
        'follow_up_date',
        'status',
        'parent_notified',
        'notified_at',
        'created_by'
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date:Y-m-d',
            'follow_up_date' => 'date:Y-m-d',
            'notified_at' => 'datetime',
            'parent_notified' => 'boolean'
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class LeaveRequest extends Model
{
    use HasFactory;

    protected $table = 'leaves';

    protected $fillable = [
        'resident_id',
        'leave_type',
        'from_date',
        'to_date',
        'reason',
        'destination',
        'parent_approval_status',
        'admin_approval_status',
        'final_status',
        'gate_pass_code',
        'approved_by',
        'approved_at'
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date:Y-m-d',
            'to_date' => 'date:Y-m-d',
            'approved_at' => 'datetime'
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }
}

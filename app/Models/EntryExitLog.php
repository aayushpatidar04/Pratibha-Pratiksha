<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class EntryExitLog extends Model
{
    use HasFactory;

    protected $table = 'entry_exit_logs';

    protected $fillable = [
        'resident_id',
        'log_type',
        'method',
        'verified_by',
        'notes'
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            
        ];
    }

    public function resident() { return $this->belongsTo(Resident::class); }
}

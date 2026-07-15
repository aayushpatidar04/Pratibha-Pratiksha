<?php
// app/Models/ResidentAmenityOverride.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentAmenityOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'resident_id', 'rent_enabled', 'mess_enabled',
        'cooler_enabled', 'custom_rent', 'custom_mess', 'custom_cooler', 'notes', 'updated_by',
    ];

    protected $casts = [
        'rent_enabled' => 'boolean',
        'mess_enabled' => 'boolean',
        'cooler_enabled' => 'boolean',
        'custom_cooler' => 'decimal:2',
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }
}
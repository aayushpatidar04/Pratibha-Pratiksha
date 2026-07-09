<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessMenu extends Model
{
    use HasFactory;

    protected $table = 'mess_menu';

    protected $fillable = [
        'building_id',
        'menu_date',
        'meal_type',
        'items',
        'special_notes',
    ];

    protected function casts(): array
    {
        return [
            'menu_date' => 'date:Y-m-d',
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
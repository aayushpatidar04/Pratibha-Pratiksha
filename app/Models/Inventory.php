<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'item_name',
        'category',
        'total_quantity',
        'in_use',
        'available',
        'damaged',
        'missing',
        'unit'
    ];

    protected function casts(): array
    {
        return [
            'total_quantity' => 'integer',
            'in_use' => 'integer',
            'available' => 'integer',
            'damaged' => 'integer',
            'missing' => 'integer',
        ];
    }

    public function studentAssignments()
    {
        return $this->hasMany(
            ResidentInventoryAssignment::class,
            'inventory_id'
        );
    }


}

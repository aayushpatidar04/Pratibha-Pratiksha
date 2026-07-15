<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Room extends Model
{
    use HasFactory;

    protected $table = 'rooms';

    protected $fillable = [
        'building_id',
        'floor_id',
        'room_number',
        'room_type',
        'capacity',
        'occupied_beds',
        'monthly_rent_per_bed',
        'room_assets',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'room_assets' => 'array',
            'monthly_rent_per_bed' => 'decimal:2',
            'capacity' => 'integer',
            'occupied_beds' => 'integer',
        ];
    }

    protected $appends = [
        'room_asset_details',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }

    public function getRoomAssetDetailsAttribute(): array
    {
        $assets = collect($this->room_assets ?? []);

        if ($assets->isEmpty()) {
            return [];
        }

        $inventoryItems = Inventory::whereIn(
            'id',
            $assets->pluck('inventory_id')
        )->get()->keyBy('id');

        return $assets
            ->map(function ($asset) use ($inventoryItems) {
                $inventory = $inventoryItems->get(
                    $asset['inventory_id']
                );

                if (!$inventory) {
                    return null;
                }

                return [
                    'inventory_id' => $inventory->id,
                    'item_name' => $inventory->item_name,
                    'quantity' => (int) ($asset['quantity'] ?? 0),
                    'unit' => $inventory->unit,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}

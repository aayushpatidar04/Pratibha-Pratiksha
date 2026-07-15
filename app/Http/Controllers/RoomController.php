<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Inventory;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Room::with([
            'building',
            'floor',
            'beds',
        ]);

        if ($search = $request->string('search')->toString()) {
            $query->where('room_number', 'like', "%{$search}%");
        }

        if ($buildingId = $request->integer('building_id')) {
            $query->where('building_id', $buildingId);
        }

        if ($floorId = $request->integer('floor_id')) {
            $query->where('floor_id', $floorId);
        }

        $status = $request->string('status')->toString();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $roomType = $request->string('room_type')->toString();

        if ($roomType && $roomType !== 'all') {
            $query->where('room_type', $roomType);
        }

        return Inertia::render('Infrastructure/Rooms', [
            'rooms' => $query
                ->orderBy('room_number')
                ->get(),

            'buildings' => Building::orderBy('name')->get([
                'id',
                'name',
            ]),

            'floors' => Floor::orderBy('floor_number')->get([
                'id',
                'name',
                'building_id',
            ]),

            'roomInventory' => Inventory::query()
                ->where('category', 'room')
                ->orderBy('item_name')
                ->get([
                    'id',
                    'item_name',
                    'available',
                    'unit',
                ]),

            'stats' => [
                'total' => Room::count(),
                'available' => Room::where('status', 'available')->count(),
                'occupied' => Room::where('status', 'occupied')->count(),
                'partiallyOccupied' => Room::where(
                    'status',
                    'partially_occupied'
                )->count(),
                'maintenance' => Room::where(
                    'status',
                    'maintenance'
                )->count(),
            ],

            'filters' => $request->only([
                'search',
                'building_id',
                'floor_id',
                'status',
                'room_type',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'building_id' => [
                'required',
                'exists:buildings,id',
            ],

            'floor_id' => [
                'required',
                'exists:floors,id',
            ],

            'room_number' => [
                'required',
                'string',
                'max:20',
            ],

            'room_type' => [
                'required',
                'in:1_seater,2_seater,3_seater,4_seater,5_seater,other',
            ],

            'capacity' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],

            'monthly_rent_per_bed' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'room_assets' => [
                'nullable',
                'array',
            ],

            'room_assets.*.inventory_id' => [
                'required',
                'integer',
                'exists:inventory,id',
            ],

            'room_assets.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $validated['room_assets'] = $this->normalizeRoomAssets(
            $validated['room_assets'] ?? []
        );

        $validated['monthly_rent_per_bed'] =
            $validated['monthly_rent_per_bed'] ?? 0;

        $validated['occupied_beds'] = 0;
        $validated['status'] = 'available';

        DB::transaction(function () use ($validated) {
            /*
             * A new room has no old assets, so allocate every
             * submitted asset from inventory.
             */
            $this->syncRoomInventory(
                oldAssets: [],
                newAssets: $validated['room_assets']
            );

            $room = Room::create($validated);

            for ($i = 1; $i <= $room->capacity; $i++) {
                Bed::create([
                    'room_id' => $room->id,
                    'bed_number' => "B{$i}",
                    'status' => 'vacant',
                ]);
            }

            Building::whereKey($room->building_id)
                ->increment('total_rooms');
        });

        return back()->with(
            'success',
            'Room created successfully.'
        );
    }

    public function update(
        Request $request,
        Room $room
    ): RedirectResponse {
        $validated = $request->validate([
            'room_number' => [
                'sometimes',
                'string',
                'max:20',
            ],

            'room_type' => [
                'sometimes',
                'in:1_seater,2_seater,3_seater,4_seater,5_seater,other',
            ],

            'capacity' => [
                'sometimes',
                'integer',
                'min:1',
                'max:10',
            ],

            'monthly_rent_per_bed' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
             * Use "sometimes" so assets are only changed when
             * room_assets is actually submitted.
             */
            'room_assets' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'room_assets.*.inventory_id' => [
                'required',
                'integer',
                'exists:inventory,id',
            ],

            'room_assets.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'status' => [
                'sometimes',
                'in:available,occupied,maintenance,partially_occupied',
            ],
        ]);

        DB::transaction(function () use ($room, &$validated) {
            /*
             * Lock the room so two updates cannot change its
             * asset allocation simultaneously.
             */
            $room = Room::query()
                ->lockForUpdate()
                ->findOrFail($room->id);

            if (array_key_exists('room_assets', $validated)) {
                $oldAssets = $this->normalizeRoomAssets(
                    $room->room_assets ?? []
                );

                $newAssets = $this->normalizeRoomAssets(
                    $validated['room_assets'] ?? []
                );

                $this->syncRoomInventory(
                    oldAssets: $oldAssets,
                    newAssets: $newAssets
                );

                $validated['room_assets'] = $newAssets;
            }

            $room->update($validated);
        });

        return back()->with(
            'success',
            'Room updated successfully.'
        );
    }

    public function destroy(Room $room): RedirectResponse
    {
        DB::transaction(function () use ($room) {
            $room = Room::query()
                ->lockForUpdate()
                ->findOrFail($room->id);

            if ((int) $room->occupied_beds > 0) {
                throw ValidationException::withMessages([
                    'room' =>
                        'An occupied room cannot be deleted.',
                ]);
            }

            $this->syncRoomInventory(
                oldAssets: $room->room_assets ?? [],
                newAssets: []
            );

            Building::whereKey($room->building_id)
                ->where('total_rooms', '>', 0)
                ->decrement('total_rooms');

            $room->delete();
        });

        return back()->with(
            'success',
            'Room deleted and its assets returned to inventory.'
        );
    }

    private function normalizeRoomAssets(array $assets): array
    {
        return collect($assets)
            ->filter(function ($asset) {
                return !empty($asset['inventory_id'])
                    && (int) ($asset['quantity'] ?? 0) > 0;
            })
            ->groupBy(function ($asset) {
                return (int) $asset['inventory_id'];
            })
            ->map(function (Collection $group, int|string $inventoryId) {
                return [
                    'inventory_id' => (int) $inventoryId,

                    /*
                     * Group duplicate inventory rows and add their
                     * quantities together.
                     */
                    'quantity' => $group->sum(
                        fn($asset) =>
                        (int) ($asset['quantity'] ?? 0)
                    ),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Update aggregate inventory quantities based on the difference
     * between the room's previous and new asset configuration.
     */
    private function syncRoomInventory(
        array $oldAssets,
        array $newAssets
    ): void {
        $oldQuantities = collect($oldAssets)
            ->mapWithKeys(fn($asset) => [
                (int) $asset['inventory_id']
                => (int) $asset['quantity'],
            ]);

        $newQuantities = collect($newAssets)
            ->mapWithKeys(fn($asset) => [
                (int) $asset['inventory_id']
                => (int) $asset['quantity'],
            ]);

        $inventoryIds = $oldQuantities
            ->keys()
            ->merge($newQuantities->keys())
            ->unique()
            ->values();

        if ($inventoryIds->isEmpty()) {
            return;
        }

        /*
         * Lock all affected inventory records until the transaction
         * is completed.
         */
        $inventoryItems = Inventory::query()
            ->whereIn('id', $inventoryIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($inventoryIds as $inventoryId) {
            $inventory = $inventoryItems->get($inventoryId);

            if (!$inventory) {
                throw ValidationException::withMessages([
                    'room_assets' =>
                        "Inventory item {$inventoryId} was not found.",
                ]);
            }

            if ($inventory->category !== 'room') {
                throw ValidationException::withMessages([
                    'room_assets' =>
                        "{$inventory->item_name} is not a room-category inventory item.",
                ]);
            }

            $oldQuantity = (int) $oldQuantities->get(
                $inventoryId,
                0
            );

            $newQuantity = (int) $newQuantities->get(
                $inventoryId,
                0
            );

            /*
             * Positive delta means more stock is being allocated.
             * Negative delta means stock is being released.
             */
            $difference = $newQuantity - $oldQuantity;

            if ($difference > 0) {
                if ((int) $inventory->available < $difference) {
                    throw ValidationException::withMessages([
                        'room_assets' =>
                            "Only {$inventory->available} {$inventory->unit} of {$inventory->item_name} are available. You need {$difference} additional {$inventory->unit}.",
                    ]);
                }

                $inventory->in_use =
                    (int) $inventory->in_use + $difference;
            }

            if ($difference < 0) {
                $quantityBeingReleased = abs($difference);

                $inventory->in_use = max(
                    0,
                    (int) $inventory->in_use
                    - $quantityBeingReleased
                );
            }

            /*
             * Recalculate instead of manually incrementing available.
             * This prevents quantity drift.
             */
            $inventory->available = max(
                0,
                (int) $inventory->total_quantity
                - (int) $inventory->in_use
                - (int) $inventory->damaged
                - (int) ($inventory->missing ?? 0)
            );

            $inventory->save();
        }
    }
}
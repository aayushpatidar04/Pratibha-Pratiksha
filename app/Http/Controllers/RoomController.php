<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Room::with(['building', 'floor']);

        if ($buildingId = $request->integer('building_id')) {
            $query->where('building_id', $buildingId);
        }
        if ($floorId = $request->integer('floor_id')) {
            $query->where('floor_id', $floorId);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($roomType = $request->string('room_type')->toString()) {
            $query->where('room_type', $roomType);
        }
        if ($search = $request->string('search')->toString()) {
            $query->where('room_number', 'like', "%{$search}%");
        }

        $rooms = $query->orderBy('room_number')->get();

        $stats = [
            'total' => Room::count(),
            'totalCapacity' => (int) Room::sum('capacity'),
            'occupiedBeds' => (int) Room::sum('occupied_beds'),
            'available' => Room::where('status', 'available')->count(),
            'occupied' => Room::where('status', 'occupied')->count(),
            'maintenance' => Room::where('status', 'maintenance')->count(),
            'partiallyOccupied' => Room::where('status', 'partially_occupied')->count(),
        ];

        return Inertia::render('Infrastructure/Rooms', [
            'rooms' => $rooms,
            'buildings' => Building::orderBy('name')->get(['id', 'name']),
            'floors' => Floor::orderBy('floor_number')->get(['id', 'name', 'building_id']),
            'stats' => $stats,
            'filters' => $request->only('building_id', 'floor_id', 'status', 'room_type', 'search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'floor_id' => 'required|exists:floors,id',
            'room_number' => 'required|string|max:20',
            'room_type' => 'required|in:1_seater,2_seater,3_seater,4_seater,5_seater,other',
            'capacity' => 'required|integer|min:1|max:10',
            'monthly_rent_per_bed' => 'nullable|numeric',
            'has_ac' => 'boolean',
            'has_wifi' => 'boolean',
            'has_attached_bath' => 'boolean',
            'has_balcony' => 'boolean',
            'has_study_table' => 'boolean',
        ]);
        $validated['monthly_rent_per_bed'] = $validated['monthly_rent_per_bed'] ?? 0;
        $validated['occupied_beds'] = 0;
        $validated['status'] = 'available';

        $room = Room::create($validated);

        for ($i = 1; $i <= $room->capacity; $i++) {
            Bed::create([
                'room_id' => $room->id,
                'bed_number' => "B{$i}",
                'status' => 'vacant',
            ]);
        }

        Building::where('id', $room->building_id)->increment('total_rooms');

        return back()->with('success', 'Room created successfully.');
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'room_number' => 'sometimes|string|max:20',
            'room_type' => 'sometimes|in:1_seater,2_seater,3_seater,4_seater,5_seater,other',
            'capacity' => 'sometimes|integer|min:1|max:10',
            'monthly_rent_per_bed' => 'sometimes|numeric',
            'has_ac' => 'boolean',
            'has_wifi' => 'boolean',
            'has_attached_bath' => 'boolean',
            'has_balcony' => 'boolean',
            'has_study_table' => 'boolean',
            'status' => 'sometimes|in:available,occupied,maintenance,partially_occupied',
        ]);

        $room->update($validated);

        return back()->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $room->beds()->delete();
        $room->delete();

        return back()->with('success', 'Room deleted successfully.');
    }
}
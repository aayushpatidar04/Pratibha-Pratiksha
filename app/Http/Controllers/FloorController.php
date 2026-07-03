<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FloorController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Floor::with('building')->orderBy('floor_number');

        if ($buildingId = $request->integer('building_id')) {
            $query->where('building_id', $buildingId);
        }

        return Inertia::render('Infrastructure/Floors', [
            'floors' => $query->get(),
            'buildings' => Building::orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $request->only('building_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'floor_number' => 'required|integer|min:0',
            'name' => 'required|string|max:50',
        ]);

        Floor::create($validated);
        Building::where('id', $validated['building_id'])->increment('total_floors');

        return back()->with('success', 'Floor created successfully.');
    }

    public function update(Request $request, Floor $floor): RedirectResponse
    {
        $validated = $request->validate([
            'floor_number' => 'sometimes|integer|min:0',
            'name' => 'sometimes|string|max:50',
        ]);

        $floor->update($validated);

        return back()->with('success', 'Floor updated successfully.');
    }

    public function destroy(Floor $floor): RedirectResponse
    {
        $floor->delete();

        return back()->with('success', 'Floor deleted successfully.');
    }
}
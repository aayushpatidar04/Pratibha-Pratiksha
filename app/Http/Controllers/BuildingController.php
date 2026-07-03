<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuildingController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Building::query();

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $buildings = $query->orderBy('name')->get();

        return Inertia::render('Infrastructure/Buildings', [
            'buildings' => $buildings,
            'filters' => $request->only('search', 'type', 'status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:buildings,code',
            'type' => 'required|in:boys,girls,mixed',
            'address' => 'nullable|string',
            'total_floors' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive,maintenance',
        ]);
        $validated['total_floors'] = $validated['total_floors'] ?? 0;
        $validated['status'] = $validated['status'] ?? 'active';

        Building::create($validated);

        return back()->with('success', 'Building created successfully.');
    }

    public function update(Request $request, Building $building): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'code' => 'sometimes|string|max:20|unique:buildings,code,'.$building->id,
            'type' => 'sometimes|in:boys,girls,mixed',
            'address' => 'nullable|string',
            'total_floors' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:active,inactive,maintenance',
        ]);

        $building->update($validated);

        return back()->with('success', 'Building updated successfully.');
    }

    public function destroy(Building $building): RedirectResponse
    {
        $building->delete();

        return back()->with('success', 'Building deleted successfully.');
    }
}
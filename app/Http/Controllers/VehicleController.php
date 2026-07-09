<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Vehicle::with('resident:id,first_name,last_name,resident_code,photo_url');

        if ($search = $request->string('search')->toString()) {
            $query->where('vehicle_number', 'like', "%{$search}%")
                ->orWhereHas('resident', fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
        }
        if ($type = $request->string('vehicle_type')->toString()) {
            $query->where('vehicle_type', $type);
        }

        $vehicles = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return Inertia::render('Residents/Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => $request->only('search', 'vehicle_type'),
            'residents' => Resident::whereIn('status', ['active', 'upcoming'])->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'resident_code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'vehicle_type' => 'required|in:two_wheeler,four_wheeler,bicycle,other',
            'vehicle_number' => 'required|string|max:30',
            'color' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:100',
        ]);

        Vehicle::create($validated);

        return back()->with('success', 'Vehicle added.');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_type' => 'sometimes|in:two_wheeler,four_wheeler,bicycle,other',
            'vehicle_number' => 'sometimes|string|max:30',
            'color' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:100',
        ]);

        $vehicle->update($validated);

        return back()->with('success', 'Vehicle updated.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return back()->with('success', 'Vehicle removed.');
    }
}
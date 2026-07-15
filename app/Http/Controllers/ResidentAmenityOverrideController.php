<?php
// app/Http/Controllers/ResidentAmenityOverrideController.php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\ResidentAmenityOverride;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResidentAmenityOverrideController extends Controller
{
    public function edit(Resident $resident): Response
    {
        $override = ResidentAmenityOverride::firstOrNew(['resident_id' => $resident->id]);

        return Inertia::render('Residents/AmenityOverride', [
            'resident' => $resident->only(['id', 'first_name', 'last_name', 'resident_code']),
            'override' => $override->exists ? $override : null,
        ]);
    }

    public function update(Request $request, Resident $resident): RedirectResponse
    {
        $validated = $request->validate([
            'rent_enabled' => 'boolean',
            'mess_enabled' => 'boolean',
            'cooler_enabled' => 'boolean',
            'custom_rent' => 'nullable|numeric|min:0',
            'custom_mess' => 'nullable|numeric|min:0',
            'custom_cooler' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        ResidentAmenityOverride::updateOrCreate(
            ['resident_id' => $resident->id],
            [
                ...$validated,
                'updated_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Amenity configuration updated.');
    }
}
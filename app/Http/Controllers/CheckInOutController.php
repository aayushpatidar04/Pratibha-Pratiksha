<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Resident;
use App\Models\ResidentStay;
use App\Models\Room;
use App\Services\RoomAllotmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckInOutController extends Controller
{
    public function index(Request $request): Response
    {
        $activeStays = ResidentStay::with(['resident', 'building', 'room', 'bed'])
            ->where('status', 'active')
            ->orderByDesc('check_in_date')
            ->get();

        $unassigned = Resident::whereDoesntHave('stays', fn($q) => $q->where('status', 'active'))
            ->whereIn('status', ['upcoming', 'active'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'resident_code', 'phone', 'photo_url', 'status']);

        return Inertia::render('CheckInOut/Index', [
            'activeStays' => $activeStays,
            'unassignedResidents' => $unassigned,
            'buildings' => Building::orderBy('name')->get(['id', 'name']),
            'floors' => Floor::orderBy('floor_number')->get(['id', 'name', 'building_id']),
            'rooms' => Room::with('beds')->orderBy('room_number')->get(['id', 'room_number', 'building_id', 'floor_id', 'capacity', 'occupied_beds', 'monthly_rent_per_bed']),
        ]);
    }

    public function checkin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'building_id' => 'required|exists:buildings,id',
            'floor_id' => 'required|exists:floors,id',
            'room_id' => 'required|exists:rooms,id',
            'bed_id' => 'required|exists:beds,id',
            'check_in_date' => 'nullable|date',
            'expected_check_out_date' => 'nullable|date',
            'rent_amount' => 'nullable|numeric',
            'deposit_amount' => 'nullable|numeric',
            'bill_type' => 'nullable|in:monthly,session',
            'notes' => 'nullable|string',
        ]);

        $resident = Resident::findOrFail($validated['resident_id']);

        try {
            RoomAllotmentService::allot($resident, $validated);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$resident->first_name} checked in successfully.");
    }

    public function checkout(Request $request, ResidentStay $stay): RedirectResponse
    {
        $validated = $request->validate([
            'actual_check_out_date' => 'nullable|date',
        ]);

        RoomAllotmentService::checkout($stay, $validated['actual_check_out_date'] ?? null);

        return back()->with('success', 'Resident checked out successfully.');
    }
}
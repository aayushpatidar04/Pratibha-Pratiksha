<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResidentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Resident::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('resident_code', 'like', "%{$search}%");
            });
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($gender = $request->string('gender')->toString()) {
            $query->where('gender', $gender);
        }
        if ($course = $request->string('course')->toString()) {
            $query->where('course', 'like', "%{$course}%");
        }

        $residents = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'total' => Resident::count(),
            'active' => Resident::where('status', 'active')->count(),
            'upcoming' => Resident::where('status', 'upcoming')->count(),
            'left' => Resident::where('status', 'left')->count(),
            'suspended' => Resident::where('status', 'suspended')->count(),
            'male' => Resident::where('gender', 'male')->count(),
            'female' => Resident::where('gender', 'female')->count(),
        ];

        return Inertia::render('Residents/Index', [
            'residents' => $residents,
            'stats' => $stats,
            'filters' => $request->only('search', 'status', 'gender', 'course'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:320',
            'phone' => 'required|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:male,female,other',
            'blood_group' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'course' => 'nullable|string|max:100',
            'year' => 'nullable|integer',
            'batch' => 'nullable|string|max:50',
            'roll_number' => 'nullable|string|max:50',
            'institute' => 'nullable|string|max:200',
            'father_name' => 'nullable|string|max:100',
            'father_phone' => 'nullable|string|max:20',
            'father_email' => 'nullable|email|max:320',
            'mother_name' => 'nullable|string|max:100',
            'mother_phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive,suspended,left,upcoming',
        ]);

        $validated['whatsapp_number'] = $validated['whatsapp_number'] ?? $validated['phone'];
        $validated['country'] = $validated['country'] ?? 'India';
        $validated['status'] = $validated['status'] ?? 'upcoming';

        $year = now()->year;
        $seq = Resident::whereYear('created_at', $year)->count() + 1;
        $validated['resident_code'] = sprintf('PP-%d-%04d', $year, $seq);
        $validated['created_by'] = $request->user()?->id;

        Resident::create($validated);

        return back()->with('success', 'Resident added successfully.');
    }

    public function update(Request $request, Resident $resident): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:320',
            'phone' => 'sometimes|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'sometimes|in:male,female,other',
            'blood_group' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'course' => 'nullable|string|max:100',
            'year' => 'nullable|integer',
            'batch' => 'nullable|string|max:50',
            'roll_number' => 'nullable|string|max:50',
            'institute' => 'nullable|string|max:200',
            'father_name' => 'nullable|string|max:100',
            'father_phone' => 'nullable|string|max:20',
            'father_email' => 'nullable|email|max:320',
            'mother_name' => 'nullable|string|max:100',
            'mother_phone' => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive,suspended,left,upcoming',
        ]);

        $resident->update($validated);

        return back()->with('success', 'Resident updated successfully.');
    }

    public function destroy(Resident $resident): RedirectResponse
    {
        $resident->delete();

        return back()->with('success', 'Resident deleted successfully.');
    }
}
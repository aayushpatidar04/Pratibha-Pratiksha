<?php

namespace App\Http\Controllers;

use App\Models\EmergencyAlert;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmergencyAlertController extends Controller
{
    public function index(Request $request): Response
    {
        $alerts = EmergencyAlert::with('resident')->orderByDesc('created_at')->paginate(20);

        $stats = [
            'active' => EmergencyAlert::where('status', 'active')->count(),
            'resolved' => EmergencyAlert::where('status', 'resolved')->count(),
            'escalated' => EmergencyAlert::where('status', 'escalated')->count(),
        ];

        return Inertia::render('Support/Emergency', [
            'alerts' => $alerts,
            'stats' => $stats,
            'residents' => Resident::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'resident_code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'category' => 'required|in:medical,fire,theft,stuck_in_lift,need_food,disaster,domestic_violence,threat,violence,suicidal,mental_depression,others',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:200',
        ]);

        $validated['status'] = 'active';

        EmergencyAlert::create($validated);

        return back()->with('success', 'Emergency alert raised. Please respond immediately.');
    }

    public function update(Request $request, EmergencyAlert $alert): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,resolved,escalated',
        ]);

        if ($validated['status'] === 'resolved') {
            $validated['resolved_by'] = $request->user()?->id;
            $validated['resolved_at'] = now();
        }

        $alert->update($validated);

        return back()->with('success', 'Alert updated.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\DisciplinaryAction;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisciplinaryActionController extends Controller
{
    public function index(Request $request): Response
    {
        $actions = DisciplinaryAction::with('resident')->orderByDesc('incident_date')->paginate(20);

        $stats = [
            'open' => DisciplinaryAction::where('status', 'open')->count(),
            'resolved' => DisciplinaryAction::where('status', 'resolved')->count(),
        ];

        return Inertia::render('Disciplinary/Index', [
            'actions' => $actions,
            'stats' => $stats,
            'residents' => Resident::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'resident_code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'incident_date' => 'required|date',
            'description' => 'required|string',
            'warning_level' => 'required|in:verbal,written,final,suspension,expulsion',
            'action_taken' => 'nullable|string',
        ]);

        $validated['status'] = 'open';
        $validated['created_by'] = $request->user()?->id;

        DisciplinaryAction::create($validated);

        return back()->with('success', 'Disciplinary record created.');
    }

    public function update(Request $request, DisciplinaryAction $disciplinaryAction): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:open,resolved,closed',
        ]);

        $disciplinaryAction->update($validated);

        return back()->with('success', 'Record updated.');
    }
}
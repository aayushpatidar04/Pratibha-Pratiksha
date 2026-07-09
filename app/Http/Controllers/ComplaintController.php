<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Complaint::with('resident');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($priority = $request->string('priority')->toString()) {
            $query->where('priority', $priority);
        }

        $complaints = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'open' => Complaint::where('status', 'open')->count(),
            'inProgress' => Complaint::where('status', 'in_progress')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
            'urgent' => Complaint::where('priority', 'urgent')->whereNotIn('status', ['resolved', 'rejected'])->count(),
        ];

        return Inertia::render('Support/Complaints', [
            'complaints' => $complaints,
            'stats' => $stats,
            'filters' => $request->only('status', 'priority'),
            'residents' => Resident::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'resident_code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'category' => 'required|in:electrical,plumbing,furniture,wifi,cleaning,security,food,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'title' => 'required|string|max:200',
            'description' => 'required|string',
        ]);

        $validated['status'] = 'open';

        Complaint::create($validated);

        return back()->with('success', 'Complaint logged successfully.');
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,escalated,rejected',
            'resolution_notes' => 'nullable|string',
        ]);

        if ($validated['status'] === 'resolved') {
            $validated['resolved_at'] = now();
        }

        $complaint->update($validated);

        return back()->with('success', 'Complaint updated successfully.');
    }

    public function destroy(Complaint $complaint): RedirectResponse
    {
        $complaint->delete();

        return back()->with('success', 'Complaint deleted.');
    }
}
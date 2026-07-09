<?php

namespace App\Http\Controllers;

use App\Models\GatePass;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GatePassController extends Controller
{
    public function index(Request $request): Response
    {
        $query = GatePass::with('resident');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $passes = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'pending' => GatePass::where('status', 'pending')->count(),
            'approved' => GatePass::where('status', 'approved')->count(),
            'used' => GatePass::where('status', 'used')->count(),
        ];

        return Inertia::render('Gate/Index', [
            'passes' => $passes,
            'stats' => $stats,
            'filters' => $request->only('status'),
            'residents' => Resident::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'resident_code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'pass_type' => 'required|in:day_out,night_pass,visitor_pass,late_entry',
            'from_time' => 'required|date',
            'to_time' => 'required|date|after:from_time',
            'purpose' => 'nullable|string',
            'visitor_name' => 'nullable|string|max:100',
            'visitor_phone' => 'nullable|string|max:20',
        ]);

        $validated['status'] = 'pending';

        GatePass::create($validated);

        return back()->with('success', 'Gate pass request created.');
    }

    public function update(Request $request, GatePass $gatePass): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,cancelled,expired,used',
        ]);

        if ($validated['status'] === 'approved') {
            $validated['approved_by'] = $request->user()?->id;
        }

        $gatePass->update($validated);

        return back()->with('success', 'Gate pass updated.');
    }
}
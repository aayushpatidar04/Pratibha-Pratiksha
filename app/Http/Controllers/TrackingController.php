<?php

namespace App\Http\Controllers;

use App\Models\EntryExitLog;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackingController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = EntryExitLog::with('resident')->orderByDesc('created_at')->paginate(30);

        return Inertia::render('Tracking/Index', [
            'logs' => $logs,
            'residents' => Resident::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'resident_code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'log_type' => 'required|in:entry,exit',
            'notes' => 'nullable|string',
        ]);

        $validated['method'] = 'manual';
        $validated['verified_by'] = $request->user()?->id;

        EntryExitLog::create($validated);

        return back()->with('success', 'Log recorded.');
    }
}
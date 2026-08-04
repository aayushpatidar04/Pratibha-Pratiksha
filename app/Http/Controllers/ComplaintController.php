<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintUpdate;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Complaint::with([
            'resident',
            'building',
            'room',
            'assignedTo',

            'updates' => fn ($query) =>
                $query
                    ->with('updatedBy:id,name')
                    ->latest('created_at'),
        ]);

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

    public function store(Request $request): RedirectResponse {
        $validated = $request->validate([
            'resident_id' =>
                'required|exists:residents,id',

            'category' =>
                'required|in:electrical,plumbing,furniture,wifi,cleaning,security,food,other',

            'priority' =>
                'required|in:low,medium,high,urgent',

            'title' =>
                'required|string|max:200',

            'description' =>
                'required|string',
        ]);

        DB::transaction(function () use (
            $request,
            $validated
        ) {
            $complaint = Complaint::create([
                ...$validated,
                'status' => 'open',
            ]);

            ComplaintUpdate::create([
                'complaint_id' =>
                    $complaint->id,

                'old_status' => null,

                'new_status' => 'open',

                'remarks' =>
                    'Complaint logged by administrator.',

                'updated_by' =>
                    $request->user()?->id,
            ]);
        });

        return back()->with(
            'success',
            'Complaint logged successfully.'
        );
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                'in:open,in_progress,resolved,escalated,rejected',
            ],

            'resolution_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        if (
            in_array(
                $validated['status'],
                ['resolved', 'rejected', 'escalated'],
                true
            )
            && blank($validated['resolution_notes'] ?? null)
        ) {
            return back()->withErrors([
                'resolution_notes' => match (
                $validated['status']
            ) {
                    'resolved' =>
                    'Please explain how the complaint was resolved.',

                    'rejected' =>
                    'Please explain why the complaint is being rejected.',

                    'escalated' =>
                    'Please explain why the complaint is being escalated.',

                    default =>
                    'Please provide remarks.',
                },
            ]);
        }

        $oldStatus = $complaint->status;
        $newStatus = $validated['status'];

        if (
            $oldStatus === $newStatus
            && blank(
                $validated['resolution_notes']
                ?? null
            )
        ) {
            return back()->with(
                'error',
                'No status or remark change was provided.'
            );
        }

        DB::transaction(function () use ($request, $complaint, $validated, $oldStatus, $newStatus) {
            $updates = [
                'status' => $newStatus,
            ];

            if (
                filled(
                    $validated['resolution_notes']
                    ?? null
                )
            ) {
                $updates['resolution_notes'] = trim(
                    $validated['resolution_notes']
                );
            }

            if ($newStatus === 'resolved') {
                $updates['resolved_at'] = now();
            } elseif ($oldStatus === 'resolved') {
                $updates['resolved_at'] = null;
            }

            $complaint->update($updates);

            ComplaintUpdate::create([
                'complaint_id' =>
                    $complaint->id,

                'old_status' =>
                    $oldStatus,

                'new_status' =>
                    $newStatus,

                'remarks' =>
                    filled(
                        $validated['resolution_notes']
                        ?? null
                    )
                    ? trim(
                        $validated['resolution_notes']
                    )
                    : null,

                'updated_by' =>
                    $request->user()?->id,
            ]);
        });

        return back()->with(
            'success',
            match ($newStatus) {
                'open' =>
                'Complaint marked as open.',

                'in_progress' =>
                'Complaint moved to in progress.',

                'resolved' =>
                'Complaint resolved successfully.',

                'escalated' =>
                'Complaint escalated successfully.',

                'rejected' =>
                'Complaint rejected successfully.',
            }
        );
    }

    public function destroy(Complaint $complaint): RedirectResponse
    {
        $complaint->delete();

        return back()->with('success', 'Complaint deleted.');
    }
}
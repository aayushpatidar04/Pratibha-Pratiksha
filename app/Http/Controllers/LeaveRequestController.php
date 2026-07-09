<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $query = LeaveRequest::with('resident');

        if ($status = $request->string('final_status')->toString()) {
            $query->where('final_status', $status);
        }

        $leaves = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'pending' => LeaveRequest::where('final_status', 'pending')->count(),
            'approved' => LeaveRequest::where('final_status', 'approved')->count(),
            'rejected' => LeaveRequest::where('final_status', 'rejected')->count(),
        ];

        return Inertia::render('Support/Leaves', [
            'leaves' => $leaves,
            'stats' => $stats,
            'filters' => $request->only('final_status'),
            'residents' => Resident::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'resident_code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'leave_type' => 'required|in:home_leave,medical_leave,emergency_leave,day_out,night_pass',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string',
            'destination' => 'nullable|string|max:200',
        ]);

        $validated['final_status'] = 'pending';

        LeaveRequest::create($validated);

        return back()->with('success', 'Leave request submitted.');
    }

    public function update(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $validated = $request->validate([
            'final_status' => 'required|in:pending,parent_approval_pending,approved,rejected,cancelled,expired',
        ]);

        if ($validated['final_status'] === 'approved') {
            $validated['admin_approval_status'] = 'approved';
            $validated['approved_by'] = $request->user()?->id;
            $validated['approved_at'] = now();
            $validated['gate_pass_code'] = $leave->gate_pass_code ?? ('GP-'.strtoupper(uniqid()));
        } elseif ($validated['final_status'] === 'rejected') {
            $validated['admin_approval_status'] = 'rejected';
        }

        $leave->update($validated);

        return back()->with('success', 'Leave request updated.');
    }

    public function destroy(LeaveRequest $leave): RedirectResponse
    {
        $leave->delete();

        return back()->with('success', 'Leave request deleted.');
    }
}
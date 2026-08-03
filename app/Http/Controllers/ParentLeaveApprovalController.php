<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentLeaveApprovalController extends Controller
{
    public function show(
        Request $request,
        string $token
    ): View {
        abort_unless(
            $request->hasValidSignature(),
            403,
            'This approval link is invalid or expired.'
        );

        $leave = LeaveRequest::query()
            ->with('resident')
            ->where(
                'parent_approval_token',
                $token
            )
            ->firstOrFail();

        return view(
            'leaves.parent-approval',
            [
                'leave' => $leave,
                'token' => $token,
            ]
        );
    }

    public function respond(
        Request $request,
        string $token
    ): RedirectResponse {
        $validated = $request->validate([
            'action' => [
                'required',
                'in:approve,reject',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $leave = LeaveRequest::query()
            ->where(
                'parent_approval_token',
                $token
            )
            ->firstOrFail();

        if (
            $leave->parent_approval_status !== 'pending'
        ) {
            return back()->with(
                'message',
                'This leave request has already been reviewed.'
            );
        }

        if (
            in_array(
                $leave->final_status,
                [
                    'cancelled',
                    'expired',
                ],
                true
            )
        ) {
            return back()->with(
                'error',
                'This leave request is no longer available for approval.'
            );
        }

        if (
            $validated['action'] === 'approve'
        ) {
            $leave->markParentApproved(
                $validated['remarks'] ?? null
            );

            return back()->with(
                'success',
                'Leave request approved successfully.'
            );
        }

        $leave->markParentRejected(
            $validated['remarks'] ?? null
        );

        return back()->with(
            'success',
            'Leave request rejected successfully.'
        );
    }
}
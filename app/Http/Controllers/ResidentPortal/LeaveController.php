<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Resident;
use App\Services\LeaveParentApprovalService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function index(
        Request $request
    ): Response {
        /** @var Resident|null $resident */
        $resident = Auth::guard(
            'resident'
        )->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:all,pending,parent_approval_pending,approved,rejected,cancelled,expired',
            ],

            'leave_type' => [
                'nullable',
                'in:home_leave,medical_leave,emergency_leave,day_out,night_pass',
            ],

            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $filters = [
            'status' =>
                $validated['status'] ?? 'all',

            'leave_type' =>
                $validated['leave_type'] ?? '',

            'search' =>
                trim($validated['search'] ?? ''),
        ];

        $baseQuery = LeaveRequest::query()
            ->where(
                'resident_id',
                $resident->id
            );

        $leaves = (clone $baseQuery)
            ->with([
                'resident:id,first_name,last_name',
                'approvedBy:id,name',
                'cancelledByResident:id,first_name,last_name',
            ])
            ->when(
                $filters['status'] !== 'all',
                fn(Builder $query) =>
                    $query->where(
                        'final_status',
                        $filters['status']
                    )
            )
            ->when(
                $filters['leave_type'] !== '',
                fn(Builder $query) =>
                    $query->where(
                        'leave_type',
                        $filters['leave_type']
                    )
            )
            ->when(
                $filters['search'] !== '',
                function (Builder $query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(
                        function (Builder $query) use ($search) {
                            $query
                                ->where(
                                    'reason',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'destination',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'gate_pass_code',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(
                fn(LeaveRequest $leave) =>
                    $this->transformLeave($leave)
            );

        $stats = [
            'total' =>
                (clone $baseQuery)->count(),

            'pending' =>
                (clone $baseQuery)
                    ->whereIn(
                        'final_status',
                        [
                            'pending',
                            'parent_approval_pending',
                        ]
                    )
                    ->count(),

            'approved' =>
                (clone $baseQuery)
                    ->where(
                        'final_status',
                        'approved'
                    )
                    ->count(),

            'rejected' =>
                (clone $baseQuery)
                    ->where(
                        'final_status',
                        'rejected'
                    )
                    ->count(),

            'cancelled' =>
                (clone $baseQuery)
                    ->where(
                        'final_status',
                        'cancelled'
                    )
                    ->count(),
        ];

        return Inertia::render(
            'ResidentPortal/Leaves/Index',
            [
                'leaves' => $leaves,
                'stats' => $stats,
                'filters' => $filters,

                'parentPhoneAvailable' =>
                    filled($resident->father_phone)
                    || filled($resident->mother_phone),
            ]
        );
    }

    public function store(
        Request $request,
        LeaveParentApprovalService $approvalService,
        NotificationService $notificationService
    ): RedirectResponse {
        /** @var Resident|null $resident */
        $resident = Auth::guard(
            'resident'
        )->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'leave_type' => [
                'required',
                'in:home_leave,medical_leave,emergency_leave,day_out,night_pass',
            ],

            'from_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'to_date' => [
                'required',
                'date',
                'after_or_equal:from_date',
            ],

            'reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],

            'destination' => [
                'nullable',
                'string',
                'max:200',
            ],
        ]);

        $hasOverlap = LeaveRequest::query()
            ->where(
                'resident_id',
                $resident->id
            )
            ->whereIn(
                'final_status',
                [
                    'pending',
                    'parent_approval_pending',
                    'approved',
                ]
            )
            ->where(function (Builder $query) use ($validated) {
                $query
                    ->whereDate(
                        'from_date',
                        '<=',
                        $validated['to_date']
                    )
                    ->whereDate(
                        'to_date',
                        '>=',
                        $validated['from_date']
                    );
            })
            ->exists();

        if ($hasOverlap) {
            return back()
                ->withInput()
                ->withErrors([
                    'from_date' =>
                        'You already have an active leave request during these dates.',
                ]);
        }

        $leave = DB::transaction(
            function () use ($resident, $validated) {
                return LeaveRequest::create([
                    ...$validated,

                    'resident_id' =>
                        $resident->id,

                    'parent_approval_status' =>
                        'pending',

                    'admin_approval_status' =>
                        'pending',

                    'final_status' =>
                        'parent_approval_pending',

                    'parent_approval_token' =>
                        Str::random(64),
                ]);
            }
        );

        $sent = $approvalService->send($leave);

        $notificationService->send(
            [
                'title' => 'New Leave Request',
                'body' => "{$resident->full_name} applied for " .
                    str_replace('_', ' ', $leave->leave_type) .
                    " ({$leave->from_date->format('d M')} – {$leave->to_date->format('d M Y')}).",
                'type' => 'leave_request_created',
                'payload' => [
                    'leave_id' => $leave->id,
                    'residentName' => $resident->full_name,
                    'leaveType' => $leave->leave_type,
                    'reason' => $leave->reason,
                    'fromDate' => $leave->from_date?->toDateString(),
                    'toDate' => $leave->to_date?->toDateString(),
                    'status' => $leave->final_status,
                ],
                'action_url' => '/support/leaves?final_status=' . $leave->final_status,
                'action_label' => 'Review Leave',
            ],
            $notificationService->recipientsForModule('leaves'),
            $leave // polymorphic actionable
        );

        return back()->with(
            'success',
            $sent
            ? 'Leave request submitted and sent to your parent for approval.'
            : 'Leave request submitted, but parent WhatsApp could not be sent. Please contact the hostel office.'
        );
    }

    public function show(
        LeaveRequest $residentLeave
    ): Response {
        $residentLeave =
            $this->residentLeaveOrFail(
                $residentLeave
            );

        $residentLeave->load([
            'approvedBy:id,name',
        ]);

        return Inertia::render(
            'ResidentPortal/Leaves/Show',
            [
                'leave' =>
                    $this->transformLeave(
                        $residentLeave
                    ),
            ]
        );
    }

    public function cancel(
        LeaveRequest $residentLeave
    ): RedirectResponse {
        $residentLeave =
            $this->residentLeaveOrFail(
                $residentLeave
            );

        if (!$residentLeave->can_cancel) {
            return back()->with(
                'error',
                'This leave request can no longer be cancelled.'
            );
        }

        $resident = Auth::guard(
            'resident'
        )->user();

        $residentLeave->update([
            'final_status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by_resident_id' =>
                $resident->id,

            /*
             * Prevent further parent action.
             */
            'parent_approval_token' => null,
        ]);

        return redirect()
            ->route('resident.leaves.index')
            ->with(
                'success',
                'Leave request cancelled successfully.'
            );
    }

    protected function residentLeaveOrFail(
        LeaveRequest $leave
    ): LeaveRequest {
        $resident = Auth::guard(
            'resident'
        )->user();

        abort_unless($resident, 401);

        abort_unless(
            (int) $leave->resident_id ===
            (int) $resident->id,
            403
        );

        return $leave;
    }

    protected function transformLeave(LeaveRequest $leave): array
    {
        return [
            'id' => $leave->id,

            // Resident
            'resident' => $leave->resident ? [
                'id' => $leave->resident->id,
                'first_name' => $leave->resident->first_name,
                'last_name' => $leave->resident->last_name,
            ] : null,

            // Leave details
            'leave_type' => $leave->leave_type,
            'leave_type_label' => $leave->leave_type_label,

            'from_date' => $leave->from_date,
            'to_date' => $leave->to_date,
            'total_days' => $leave->total_days,

            'reason' => $leave->reason,
            'destination' => $leave->destination,

            // Parent approval
            'parent_approval_status' => $leave->parent_approval_status,
            'parent_approval_sent_at' => $leave->parent_approval_sent_at,
            'parent_responded_at' => $leave->parent_responded_at,
            'parent_remarks' => $leave->parent_remarks,

            // Admin approval
            'admin_approval_status' => $leave->admin_approval_status,
            'admin_remarks' => $leave->admin_remarks,

            // Final status
            'final_status' => $leave->final_status,
            'final_status_label' => $leave->final_status_label,

            // Gate pass
            'gate_pass_code' => $leave->gate_pass_code,

            // Approval
            'approved_at' => $leave->approved_at,
            'approved_by' => $leave->approvedBy?->name,

            // Cancellation
            'cancelled_at' => $leave->cancelled_at,
            'cancelled_by_resident' => $leave->cancelledByResident ? [
                'id' => $leave->cancelledByResident->id,
                'first_name' => $leave->cancelledByResident->first_name,
                'last_name' => $leave->cancelledByResident->last_name,
            ] : null,

            // Other
            'can_cancel' => $leave->can_cancel,
            'created_at' => $leave->created_at,
            'updated_at' => $leave->updated_at,
        ];
    }
}
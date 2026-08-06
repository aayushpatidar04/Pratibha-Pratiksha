<?php

namespace App\Http\Middleware;

use App\Models\Complaint;
use App\Models\EmergencyAlert;
use App\Models\LeaveRequest;
use App\Models\Notice;
use App\Models\RoomChangeRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = Auth::guard('web')->user();
        $resident = Auth::guard('resident')->user();
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ?? null,
                'permissions' => $user ? $this->resolvePermissions($user) : [],
                'resident' => $resident ?? null,
            ],
            'residentPortal' => $resident
                ? fn () => $this
                    ->residentPortalData($resident)
                : null,
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'bulk_upload_summary' => fn() =>
                    $request->session()->get('bulk_upload_summary'),

                'bulk_upload_failures' => fn() =>
                    $request->session()->get('bulk_upload_failures'),
            ],
        ];
    }

    protected function resolvePermissions($user): array
    {
        $resolved = [];

        foreach (config('modules.modules') as $module) {
            $resolved[$module['key']] = $user->hasFullAccess()
                ? $module['actions']
                : array_values(array_intersect($module['actions'], $user->permissions[$module['key']] ?? []));
        }

        return $resolved;
    }

    protected function residentPortalData(
        $resident
    ): array {
        $visibleNotices = Notice::query()
            ->published()
            ->visibleToResident($resident);

        $unreadNotices = (clone $visibleNotices)
            ->whereDoesntHave(
                'reads',
                fn (Builder $query) =>
                    $query->where(
                        'resident_id',
                        $resident->id
                    )
            )
            ->count();

        $pendingAcknowledgements =
            (clone $visibleNotices)
                ->where(
                    'requires_acknowledgement',
                    true
                )
                ->where(function (
                    Builder $query
                ) use ($resident): void {
                    $query
                        ->whereDoesntHave(
                            'reads',
                            fn (Builder $readQuery) =>
                                $readQuery->where(
                                    'resident_id',
                                    $resident->id
                                )
                        )
                        ->orWhereHas(
                            'reads',
                            fn (Builder $readQuery) =>
                                $readQuery
                                    ->where(
                                        'resident_id',
                                        $resident->id
                                    )
                                    ->whereNull(
                                        'acknowledged_at'
                                    )
                        );
                })
                ->count();

        $activeEmergency = EmergencyAlert::query()
            ->where(
                'resident_id',
                $resident->id
            )
            ->whereIn('status', [
                'active',
                'escalated',
            ])
            ->latest('created_at')
            ->first([
                'id',
                'category',
                'status',
                'location',
                'created_at',
            ]);

        $latestNotifications = collect();

        /*
        * Latest unread notices.
        */
        $noticeNotifications =
            (clone $visibleNotices)
                ->whereDoesntHave(
                    'reads',
                    fn (Builder $query) =>
                        $query->where(
                            'resident_id',
                            $resident->id
                        )
                )
                ->latest('published_at')
                ->limit(4)
                ->get([
                    'id',
                    'title',
                    'priority',
                    'published_at',
                    'created_at',
                ])
                ->map(fn (Notice $notice) => [
                    'id' =>
                        'notice-' . $notice->id,

                    'type' => 'notice',

                    'title' =>
                        $notice->title,

                    'message' =>
                        $notice->priority === 'urgent'
                            ? 'Urgent hostel notice'
                            : 'New hostel notice',

                    'created_at' =>
                        $notice->published_at
                        ?? $notice->created_at,

                    'href' => route(
                        'resident.notices.show',
                        [
                            'notice' =>
                                $notice->id,
                        ]
                    ),

                    'tone' =>
                        $notice->priority === 'urgent'
                            ? 'red'
                            : 'indigo',
                ]);

        $latestNotifications =
            $latestNotifications->concat(
                $noticeNotifications
            );

        /*
        * Latest leave status.
        */
        $latestLeave = LeaveRequest::query()
            ->where(
                'resident_id',
                $resident->id
            )
            ->latest('updated_at')
            ->first([
                'id',
                'leave_type',
                'final_status',
                'updated_at',
            ]);

        if ($latestLeave) {
            $latestNotifications->push([
                'id' =>
                    'leave-' . $latestLeave->id,

                'type' => 'leave',

                'title' =>
                    'Leave Request '
                    . str($latestLeave->final_status)
                        ->replace('_', ' ')
                        ->title(),

                'message' =>
                    str($latestLeave->leave_type)
                        ->replace('_', ' ')
                        ->title()
                        ->toString(),

                'created_at' =>
                    $latestLeave->updated_at,

                'href' => route(
                    'resident.leaves.index'
                ),

                'tone' => match (
                    $latestLeave->final_status
                ) {
                    'approved' => 'green',
                    'rejected' => 'red',
                    default => 'amber',
                },
            ]);
        }

        /*
        * Latest complaint status.
        */
        $latestComplaint = Complaint::query()
            ->where(
                'resident_id',
                $resident->id
            )
            ->latest('updated_at')
            ->first([
                'id',
                'title',
                'status',
                'updated_at',
            ]);

        if ($latestComplaint) {
            $latestNotifications->push([
                'id' =>
                    'complaint-'
                    . $latestComplaint->id,

                'type' => 'complaint',

                'title' =>
                    'Complaint '
                    . str($latestComplaint->status)
                        ->replace('_', ' ')
                        ->title(),

                'message' =>
                    $latestComplaint->title,

                'created_at' =>
                    $latestComplaint->updated_at,

                'href' => route(
                    'resident.complaints.index'
                ),

                'tone' => match (
                    $latestComplaint->status
                ) {
                    'resolved' => 'green',
                    'rejected' => 'red',
                    default => 'amber',
                },
            ]);
        }

        /*
        * Latest room-change request.
        */
        $latestRoomRequest =
            RoomChangeRequest::query()
                ->where(
                    'resident_id',
                    $resident->id
                )
                ->latest('updated_at')
                ->first([
                    'id',
                    'status',
                    'updated_at',
                ]);

        if ($latestRoomRequest) {
            $latestNotifications->push([
                'id' =>
                    'room-request-'
                    . $latestRoomRequest->id,

                'type' => 'request',

                'title' =>
                    'Room Change '
                    . str($latestRoomRequest->status)
                        ->replace('_', ' ')
                        ->title(),

                'message' =>
                    'Your room change request was updated.',

                'created_at' =>
                    $latestRoomRequest
                        ->updated_at,

                'href' => route(
                    'resident.room-change-requests.index'
                ),

                'tone' => match (
                    $latestRoomRequest->status
                ) {
                    'approved' => 'green',
                    'rejected' => 'red',
                    default => 'purple',
                },
            ]);
        }

        if ($activeEmergency) {
            $latestNotifications->push([
                'id' =>
                    'emergency-'
                    . $activeEmergency->id,

                'type' => 'emergency',

                'title' =>
                    'Emergency Alert Active',

                'message' =>
                    str($activeEmergency->category)
                        ->replace('_', ' ')
                        ->title()
                        ->toString(),

                'created_at' =>
                    $activeEmergency->created_at,

                'href' => route(
                    'resident.emergency.index'
                ),

                'tone' => 'red',
            ]);
        }

        $latestNotifications =
            $latestNotifications
                ->sortByDesc(
                    fn (array $item) =>
                        optional(
                            $item['created_at']
                        )?->timestamp ?? 0
                )
                ->take(8)
                ->values();

        return [
            'counts' => [
                'pending_leaves' =>
                    LeaveRequest::query()
                        ->where(
                            'resident_id',
                            $resident->id
                        )
                        ->whereIn(
                            'final_status',
                            [
                                'pending',
                                'parent_approval_pending',
                            ]
                        )
                        ->count(),

                'open_complaints' =>
                    Complaint::query()
                        ->where(
                            'resident_id',
                            $resident->id
                        )
                        ->whereIn(
                            'status',
                            [
                                'open',
                                'in_progress',
                                'escalated',
                            ]
                        )
                        ->count(),

                'pending_room_requests' =>
                    RoomChangeRequest::query()
                        ->where(
                            'resident_id',
                            $resident->id
                        )
                        ->where(
                            'status',
                            'pending'
                        )
                        ->count(),

                'active_emergencies' =>
                    $activeEmergency ? 1 : 0,

                'unread_notices' =>
                    $unreadNotices,

                'pending_acknowledgements' =>
                    $pendingAcknowledgements,

                'total_notifications' =>
                    $unreadNotices
                    + $pendingAcknowledgements
                    + ($activeEmergency ? 1 : 0),
            ],

            'active_emergency' =>
                $activeEmergency
                    ? [
                        'id' =>
                            $activeEmergency->id,

                        'category' =>
                            $activeEmergency->category,

                        'status' =>
                            $activeEmergency->status,

                        'location' =>
                            $activeEmergency->location,

                        'created_at' =>
                            $activeEmergency->created_at,
                    ]
                    : null,

            'notifications' =>
                $latestNotifications,
        ];
    }

}

<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintUpdate;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'in:all,open,in_progress,resolved,escalated,rejected',
            ],

            'priority' => [
                'nullable',
                'in:all,low,medium,high,urgent',
            ],

            'category' => [
                'nullable',
                'in:all,electrical,plumbing,furniture,wifi,cleaning,security,food,other',
            ],
        ]);

        $filters = [
            'search' => trim($validated['search'] ?? ''),
            'status' => $validated['status'] ?? 'all',
            'priority' => $validated['priority'] ?? 'all',
            'category' => $validated['category'] ?? 'all',
        ];

        $baseQuery = Complaint::query()
            ->where('resident_id', $resident->id);

        $complaints = (clone $baseQuery)
            ->with([
                'building:id,name',
                'room:id,room_number',
                'assignedTo:id,name',
            ])
            ->when(
                $filters['search'] !== '',
                function (Builder $query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function (Builder $query) use ($search) {
                        $query
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('resolution_notes', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                $filters['status'] !== 'all',
                fn (Builder $query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                $filters['priority'] !== 'all',
                fn (Builder $query) => $query->where(
                    'priority',
                    $filters['priority']
                )
            )
            ->when(
                $filters['category'] !== 'all',
                fn (Builder $query) => $query->where(
                    'category',
                    $filters['category']
                )
            )
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString()
            ->through(
                fn (Complaint $complaint) =>
                    $this->transformComplaint($complaint)
            );

        $stats = [
            'total' => (clone $baseQuery)->count(),

            'open' => (clone $baseQuery)
                ->where('status', 'open')
                ->count(),

            'in_progress' => (clone $baseQuery)
                ->where('status', 'in_progress')
                ->count(),

            'resolved' => (clone $baseQuery)
                ->where('status', 'resolved')
                ->count(),

            'escalated' => (clone $baseQuery)
                ->where('status', 'escalated')
                ->count(),

            'rejected' => (clone $baseQuery)
                ->where('status', 'rejected')
                ->count(),

            'active' => (clone $baseQuery)
                ->whereIn('status', [
                    'open',
                    'in_progress',
                    'escalated',
                ])
                ->count(),

            'urgent_active' => (clone $baseQuery)
                ->where('priority', 'urgent')
                ->whereIn('status', [
                    'open',
                    'in_progress',
                    'escalated',
                ])
                ->count(),
        ];

        $resident->load([
            'currentStay.building:id,name',
            'currentStay.room:id,room_number',
            'currentStay.bed:id,bed_number',
        ]);

        $stay = $resident->currentStay;

        return Inertia::render(
            'ResidentPortal/Complaints/Index',
            [
                'complaints' => $complaints,
                'stats' => $stats,
                'filters' => $filters,

                'currentStay' => $stay
                    ? [
                        'id' => $stay->id,

                        'building_id' => $stay->building_id,
                        'building_name' => $stay->building?->name,

                        'room_id' => $stay->room_id,
                        'room_number' => $stay->room?->room_number,

                        'bed_number' => $stay->bed?->bed_number,
                    ]
                    : null,
            ]
        );
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'category' => [
                'required',
                'in:electrical,plumbing,furniture,wifi,cleaning,security,food,other',
            ],

            'priority' => [
                'required',
                'in:low,medium,high,urgent',
            ],

            'title' => [
                'required',
                'string',
                'min:5',
                'max:200',
            ],

            'description' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
        ]);

        $resident->load([
            'currentStay',
        ]);

        $stay = $resident->currentStay;

        $complaint = DB::transaction(function () use (
            $resident,
            $stay,
            $validated
        ) {
            $complaint = Complaint::create([
                'resident_id' => $resident->id,

                'building_id' =>
                    $stay?->building_id,

                'room_id' =>
                    $stay?->room_id,

                'category' =>
                    $validated['category'],

                'priority' =>
                    $validated['priority'],

                'title' =>
                    $validated['title'],

                'description' =>
                    $validated['description'],

                'status' => 'open',
            ]);

            ComplaintUpdate::create([
                'complaint_id' =>
                    $complaint->id,

                'old_status' => null,

                'new_status' => 'open',

                'remarks' =>
                    'Complaint submitted by resident.',

                'updated_by' => null,
            ]);

            return $complaint;
        });

        return redirect()
            ->route(
                'resident.complaints.show',
                ['complaint' => $complaint->id]
            )
            ->with(
                'success',
                'Complaint submitted successfully.'
            );
    }

    public function show(Complaint $complaint): Response {
        $complaint = $this
            ->residentComplaintOrFail(
                $complaint
            );

        $complaint->load([
            'building:id,name',
            'room:id,room_number',
            'assignedTo:id,name',

            'updates.updatedBy:id,name',
        ]);

        return Inertia::render(
            'ResidentPortal/Complaints/Show',
            [
                'complaint' =>
                    $this->transformComplaint(
                        $complaint
                    ),
            ]
        );
    }

    public function rate(Request $request, Complaint $complaint): RedirectResponse {
        $complaint = $this->residentComplaintOrFail(
            $complaint
        );

        $validated = $request->validate([
            'rating' => [
                'required',
                'integer',
                'between:1,5',
            ],
        ]);

        if ($complaint->status !== 'resolved') {
            return back()->with(
                'error',
                'Only resolved complaints can be rated.'
            );
        }

        if ($complaint->rating !== null) {
            return back()->with(
                'error',
                'You have already rated this complaint.'
            );
        }

        $complaint->update([
            'rating' => $validated['rating'],
        ]);

        return back()->with(
            'success',
            'Thank you for rating the resolution.'
        );
    }

    public function destroy(Complaint $complaint): RedirectResponse {
        $complaint = $this->residentComplaintOrFail(
            $complaint
        );

        if (!$complaint->can_delete) {
            return back()->with(
                'error',
                'This complaint can no longer be deleted because processing has started.'
            );
        }

        $complaint->delete();

        return redirect()
            ->route('resident.complaints.index')
            ->with(
                'success',
                'Complaint deleted successfully.'
            );
    }

    protected function residentComplaintOrFail(Complaint $complaint): Complaint {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        abort_unless(
            (int) $complaint->resident_id ===
                (int) $resident->id,
            403
        );

        return $complaint;
    }

    protected function transformComplaint(Complaint $complaint): array {
        return [
            'id' => $complaint->id,

            'category' => $complaint->category,
            'category_label' => $complaint->category_label,

            'priority' => $complaint->priority,
            'priority_label' => $complaint->priority_label,

            'title' => $complaint->title,
            'description' => $complaint->description,

            'status' => $complaint->status,
            'status_label' => $complaint->status_label,

            'building' => $complaint->building
                ? [
                    'id' => $complaint->building->id,
                    'name' => $complaint->building->name,
                ]
                : null,

            'room' => $complaint->room
                ? [
                    'id' => $complaint->room->id,
                    'room_number' => $complaint->room->room_number,
                ]
                : null,

            'assigned_to' => $complaint->assignedTo
                ? [
                    'id' => $complaint->assignedTo->id,
                    'name' => $complaint->assignedTo->name,
                ]
                : null,

            'resolution_notes' =>
                $complaint->resolution_notes,

            'updates' => $complaint->relationLoaded('updates')
                ? $complaint->updates
                    ->sortBy('created_at')
                    ->values()
                    ->map(function (
                        ComplaintUpdate $update
                    ) {
                        return [
                            'id' =>
                                $update->id,

                            'old_status' =>
                                $update->old_status,

                            'new_status' =>
                                $update->new_status,

                            'remarks' =>
                                $update->remarks,

                            'updated_by' =>
                                $update->updatedBy
                                    ? [
                                        'id' =>
                                            $update->updatedBy->id,

                                        'name' =>
                                            $update->updatedBy->name,
                                    ]
                                    : null,

                            'created_at' =>
                                $update->created_at,
                        ];
                    })
                : [],

            'resolved_at' =>
                $complaint->resolved_at,

            'rating' =>
                $complaint->rating,

            'can_rate' =>
                $complaint->can_rate,

            'can_delete' =>
                $complaint->can_delete,

            'created_at' =>
                $complaint->created_at,

            'updated_at' =>
                $complaint->updated_at,
        ];
    }
}
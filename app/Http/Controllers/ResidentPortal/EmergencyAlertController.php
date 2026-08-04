<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\EmergencyAlert;
use App\Models\EmergencyAlertUpdate;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EmergencyAlertController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:all,active,escalated,resolved',
            ],

            'category' => [
                'nullable',
                'in:all,medical,fire,theft,stuck_in_lift,need_food,disaster,domestic_violence,threat,violence,suicidal,mental_depression,others',
            ],
        ]);

        $filters = [
            'status' =>
                $validated['status'] ?? 'all',

            'category' =>
                $validated['category'] ?? 'all',
        ];

        $baseQuery = EmergencyAlert::query()
            ->where(
                'resident_id',
                $resident->id
            );

        $alerts = (clone $baseQuery)
            ->with([
                'building:id,name',
                'room:id,room_number',
                'assignedTo:id,name',
                'acknowledgedBy:id,name',
                'resolvedBy:id,name',
            ])
            ->when(
                $filters['status'] !== 'all',
                fn (Builder $query) =>
                    $query->where(
                        'status',
                        $filters['status']
                    )
            )
            ->when(
                $filters['category'] !== 'all',
                fn (Builder $query) =>
                    $query->where(
                        'category',
                        $filters['category']
                    )
            )
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString()
            ->through(
                fn (EmergencyAlert $alert) =>
                    $this->transformAlert($alert)
            );

        $stats = [
            'total' =>
                (clone $baseQuery)->count(),

            'active' =>
                (clone $baseQuery)
                    ->where('status', 'active')
                    ->count(),

            'escalated' =>
                (clone $baseQuery)
                    ->where('status', 'escalated')
                    ->count(),

            'resolved' =>
                (clone $baseQuery)
                    ->where('status', 'resolved')
                    ->count(),
        ];

        $resident->load([
            'currentStay.building:id,name',
            'currentStay.room:id,room_number',
            'currentStay.bed:id,bed_number',
        ]);

        $stay = $resident->currentStay;

        return Inertia::render(
            'ResidentPortal/Emergency/Index',
            [
                'alerts' => $alerts,
                'stats' => $stats,
                'filters' => $filters,

                'currentStay' => $stay
                    ? [
                        'building_name' =>
                            $stay->building?->name,

                        'room_number' =>
                            $stay->room?->room_number,

                        'bed_number' =>
                            $stay->bed?->bed_number,
                    ]
                    : null,

                'hasActiveAlert' =>
                    (clone $baseQuery)
                        ->whereIn('status', [
                            'active',
                            'escalated',
                        ])
                        ->exists(),
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'category' => [
                'required',
                'in:medical,fire,theft,stuck_in_lift,need_food,disaster,domestic_violence,threat,violence,suicidal,mental_depression,others',
            ],

            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'location' => [
                'nullable',
                'string',
                'max:200',
            ],
        ]);

        $hasActiveAlert = EmergencyAlert::query()
            ->where(
                'resident_id',
                $resident->id
            )
            ->whereIn('status', [
                'active',
                'escalated',
            ])
            ->exists();

        if ($hasActiveAlert) {
            return back()->with(
                'error',
                'You already have an active emergency alert. Hostel staff have been notified.'
            );
        }

        $resident->load('currentStay');

        $stay = $resident->currentStay;

        $alert = DB::transaction(
            function () use (
                $resident,
                $stay,
                $validated
            ): EmergencyAlert {
                $alert = EmergencyAlert::create([
                    'resident_id' =>
                        $resident->id,

                    'building_id' =>
                        $stay?->building_id,

                    'room_id' =>
                        $stay?->room_id,

                    'category' =>
                        $validated['category'],

                    'description' =>
                        $validated['description'] ?? null,

                    'location' =>
                        filled($validated['location'] ?? null)
                            ? trim($validated['location'])
                            : $this->defaultLocation(
                                $stay
                            ),

                    'status' => 'active',
                ]);

                EmergencyAlertUpdate::create([
                    'emergency_alert_id' =>
                        $alert->id,

                    'old_status' => null,

                    'new_status' => 'active',

                    'remarks' =>
                        'Emergency alert raised by resident.',

                    'updated_by' => null,

                    'updated_by_resident' =>
                        true,
                ]);

                return $alert;
            }
        );

        /*
         * Add your existing push notification,
         * WhatsApp gateway or staff notification job here.
         *
         * Example:
         *
         * EmergencyAlertRaised::dispatch($alert);
         */

        return redirect()
            ->route(
                'resident.emergency.show',
                [
                    'alert' => $alert->id,
                ]
            )
            ->with(
                'success',
                'Emergency alert raised. Hostel staff have been notified.'
            );
    }

    public function show(
        EmergencyAlert $alert
    ): Response {
        $alert = $this->residentAlertOrFail(
            $alert
        );

        $alert->load([
            'building:id,name',
            'room:id,room_number',
            'assignedTo:id,name',
            'acknowledgedBy:id,name',
            'resolvedBy:id,name',

            'updates.updatedBy:id,name',
        ]);

        return Inertia::render(
            'ResidentPortal/Emergency/Show',
            [
                'alert' =>
                    $this->transformAlert(
                        $alert
                    ),
            ]
        );
    }

    protected function residentAlertOrFail(
        EmergencyAlert $alert
    ): EmergencyAlert {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        abort_unless(
            (int) $alert->resident_id ===
                (int) $resident->id,
            403
        );

        return $alert;
    }

    protected function defaultLocation(
        $stay
    ): ?string {
        if (!$stay) {
            return null;
        }

        $stay->loadMissing([
            'building:id,name',
            'room:id,room_number',
            'bed:id,bed_number',
        ]);

        return collect([
            $stay->building?->name,
            $stay->room?->room_number
                ? 'Room ' .
                    $stay->room->room_number
                : null,
            $stay->bed?->bed_number
                ? 'Bed ' .
                    $stay->bed->bed_number
                : null,
        ])
            ->filter()
            ->implode(' · ');
    }

    protected function transformAlert(
        EmergencyAlert $alert
    ): array {
        return [
            'id' => $alert->id,

            'category' => $alert->category,

            'category_label' =>
                $alert->category_label,

            'description' =>
                $alert->description,

            'location' =>
                $alert->location,

            'status' =>
                $alert->status,

            'status_label' =>
                $alert->status_label,

            'is_active' =>
                $alert->is_active,

            'building' => $alert->building
                ? [
                    'id' =>
                        $alert->building->id,

                    'name' =>
                        $alert->building->name,
                ]
                : null,

            'room' => $alert->room
                ? [
                    'id' =>
                        $alert->room->id,

                    'room_number' =>
                        $alert->room->room_number,
                ]
                : null,

            'assigned_to' =>
                $alert->assignedTo?->name,

            'acknowledged_by' =>
                $alert->acknowledgedBy?->name,

            'acknowledged_at' =>
                $alert->acknowledged_at,

            'escalation_notes' =>
                $alert->escalation_notes,

            'resolution_notes' =>
                $alert->resolution_notes,

            'resolved_by' =>
                $alert->resolvedBy?->name,

            'resolved_at' =>
                $alert->resolved_at,

            'updates' =>
                $alert->relationLoaded('updates')
                    ? $alert->updates
                        ->map(
                            fn (
                                EmergencyAlertUpdate $update
                            ) => [
                                'id' =>
                                    $update->id,

                                'old_status' =>
                                    $update->old_status,

                                'new_status' =>
                                    $update->new_status,

                                'remarks' =>
                                    $update->remarks,

                                'updated_by' =>
                                    $update->updatedBy?->name,

                                'updated_by_resident' =>
                                    $update
                                        ->updated_by_resident,

                                'created_at' =>
                                    $update->created_at,
                            ]
                        )
                        ->values()
                    : [],

            'created_at' =>
                $alert->created_at,

            'updated_at' =>
                $alert->updated_at,
        ];
    }
}
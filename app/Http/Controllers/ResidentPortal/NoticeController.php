<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\NoticeAttachment;
use App\Models\NoticeRead;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NoticeController extends Controller
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

            'category' => [
                'nullable',
                'in:all,general,academic,hostel,mess,maintenance,event,payment,emergency,policy,other',
            ],

            'priority' => [
                'nullable',
                'in:all,normal,important,urgent',
            ],

            'read_status' => [
                'nullable',
                'in:all,unread,read,acknowledgement_pending,acknowledged',
            ],
        ]);

        $filters = [
            'search' => trim(
                $validated['search'] ?? ''
            ),

            'category' =>
                $validated['category'] ?? 'all',

            'priority' =>
                $validated['priority'] ?? 'all',

            'read_status' =>
                $validated['read_status'] ?? 'all',
        ];

        $resident->loadMissing([
            'currentStay',
        ]);

        $baseQuery = Notice::query()
            ->published()
            ->visibleToResident($resident);

        $notices = (clone $baseQuery)
            ->with([
                'attachments',

                'reads' => fn ($query) =>
                    $query->where(
                        'resident_id',
                        $resident->id
                    ),
            ])
            ->when(
                $filters['search'] !== '',
                function (
                    $query
                ) use ($filters): void {
                    $search = $filters['search'];

                    $query->where(function (
                        $query
                    ) use ($search): void {
                        $query
                            ->where(
                                'title',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'summary',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'content',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )
            ->when(
                $filters['category'] !== 'all',
                fn ($query) =>
                    $query->where(
                        'category',
                        $filters['category']
                    )
            )
            ->when(
                $filters['priority'] !== 'all',
                fn ($query) =>
                    $query->where(
                        'priority',
                        $filters['priority']
                    )
            )
            ->when(
                $filters['read_status'] === 'unread',
                fn ($query) =>
                    $query->whereDoesntHave(
                        'reads',
                        fn ($readQuery) =>
                            $readQuery->where(
                                'resident_id',
                                $resident->id
                            )
                    )
            )
            ->when(
                $filters['read_status'] === 'read',
                fn ($query) =>
                    $query->whereHas(
                        'reads',
                        fn ($readQuery) =>
                            $readQuery
                                ->where(
                                    'resident_id',
                                    $resident->id
                                )
                                ->whereNotNull(
                                    'first_read_at'
                                )
                    )
            )
            ->when(
                $filters['read_status']
                    === 'acknowledgement_pending',
                fn ($query) =>
                    $query
                        ->where(
                            'requires_acknowledgement',
                            true
                        )
                        ->where(function (
                            $query
                        ) use ($resident): void {
                            $query
                                ->whereDoesntHave(
                                    'reads',
                                    fn ($readQuery) =>
                                        $readQuery->where(
                                            'resident_id',
                                            $resident->id
                                        )
                                )
                                ->orWhereHas(
                                    'reads',
                                    fn ($readQuery) =>
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
            )
            ->when(
                $filters['read_status']
                    === 'acknowledged',
                fn ($query) =>
                    $query->whereHas(
                        'reads',
                        fn ($readQuery) =>
                            $readQuery
                                ->where(
                                    'resident_id',
                                    $resident->id
                                )
                                ->whereNotNull(
                                    'acknowledged_at'
                                )
                    )
            )
            ->orderByDesc('is_pinned')
            ->orderByRaw(
                "CASE
                    WHEN priority = 'urgent' THEN 1
                    WHEN priority = 'important' THEN 2
                    ELSE 3
                END"
            )
            ->latest('published_at')
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString()
            ->through(
                fn (Notice $notice) =>
                    $this->transformNotice(
                        $notice,
                        $resident
                    )
            );

        $stats = [
            'total' =>
                (clone $baseQuery)->count(),

            'unread' =>
                (clone $baseQuery)
                    ->whereDoesntHave(
                        'reads',
                        fn ($query) =>
                            $query->where(
                                'resident_id',
                                $resident->id
                            )
                    )
                    ->count(),

            'important' =>
                (clone $baseQuery)
                    ->whereIn('priority', [
                        'important',
                        'urgent',
                    ])
                    ->count(),

            'acknowledgement_pending' =>
                (clone $baseQuery)
                    ->where(
                        'requires_acknowledgement',
                        true
                    )
                    ->where(function (
                        $query
                    ) use ($resident): void {
                        $query
                            ->whereDoesntHave(
                                'reads',
                                fn ($readQuery) =>
                                    $readQuery->where(
                                        'resident_id',
                                        $resident->id
                                    )
                            )
                            ->orWhereHas(
                                'reads',
                                fn ($readQuery) =>
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
                    ->count(),

            'acknowledged' =>
                (clone $baseQuery)
                    ->whereHas(
                        'reads',
                        fn ($query) =>
                            $query
                                ->where(
                                    'resident_id',
                                    $resident->id
                                )
                                ->whereNotNull(
                                    'acknowledged_at'
                                )
                    )
                    ->count(),
        ];

        return Inertia::render(
            'ResidentPortal/Notices/Index',
            [
                'notices' => $notices,
                'stats' => $stats,
                'filters' => $filters,
            ]
        );
    }

    public function show(
        Notice $notice
    ): Response {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $resident->loadMissing([
            'currentStay',
        ]);

        $notice = Notice::query()
            ->published()
            ->visibleToResident($resident)
            ->with([
                'attachments',

                'reads' => fn ($query) =>
                    $query->where(
                        'resident_id',
                        $resident->id
                    ),

                'createdBy:id,name',
            ])
            ->findOrFail($notice->id);

        $read = NoticeRead::firstOrCreate(
            [
                'notice_id' => $notice->id,
                'resident_id' => $resident->id,
            ],
            [
                'read_count' => 0,
            ]
        );

        $read->markRead();

        $notice->load([
            'reads' => fn ($query) =>
                $query->where(
                    'resident_id',
                    $resident->id
                ),
        ]);

        return Inertia::render(
            'ResidentPortal/Notices/Show',
            [
                'notice' =>
                    $this->transformNotice(
                        $notice,
                        $resident
                    ),
            ]
        );
    }

    public function acknowledge(
        Request $request,
        Notice $notice
    ): RedirectResponse {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $resident->loadMissing([
            'currentStay',
        ]);

        $notice = Notice::query()
            ->published()
            ->visibleToResident($resident)
            ->findOrFail($notice->id);

        if (!$notice->requires_acknowledgement) {
            return back()->with(
                'error',
                'This notice does not require acknowledgement.'
            );
        }

        $read = NoticeRead::firstOrCreate(
            [
                'notice_id' => $notice->id,
                'resident_id' => $resident->id,
            ],
            [
                'read_count' => 0,
            ]
        );

        if (!$read->first_read_at) {
            $read->markRead();
        }

        if ($read->acknowledged_at) {
            return back()->with(
                'success',
                'You have already acknowledged this notice.'
            );
        }

        $read->acknowledge(
            $request->ip()
        );

        return back()->with(
            'success',
            'Notice acknowledged successfully.'
        );
    }

    public function downloadAttachment(
        Notice $notice,
        NoticeAttachment $attachment
    ): BinaryFileResponse {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $resident->loadMissing([
            'currentStay',
        ]);

        Notice::query()
            ->published()
            ->visibleToResident($resident)
            ->findOrFail($notice->id);

        abort_unless(
            (int) $attachment->notice_id ===
                (int) $notice->id,
            404
        );

        $path = storage_path(
            'app/public/' . $attachment->file_path
        );

        abort_unless(
            is_file($path),
            404
        );

        return response()->download(
            $path,
            $attachment->original_name
        );
    }

    private function transformNotice(
        Notice $notice,
        Resident $resident
    ): array {
        $read = $notice->relationLoaded('reads')
            ? $notice->reads
                ->firstWhere(
                    'resident_id',
                    $resident->id
                )
            : null;

        return [
            'id' => $notice->id,

            'title' => $notice->title,
            'summary' => $notice->summary,
            'content' => $notice->content,

            'category' => $notice->category,

            'category_label' =>
                $notice->category_label,

            'priority' => $notice->priority,

            'priority_label' =>
                $notice->priority_label,

            'is_pinned' =>
                $notice->is_pinned,

            'requires_acknowledgement' =>
                $notice
                    ->requires_acknowledgement,

            'publish_at' =>
                $notice->publish_at,

            'published_at' =>
                $notice->published_at,

            'expires_at' =>
                $notice->expires_at,

            'is_read' =>
                $read?->first_read_at !== null,

            'first_read_at' =>
                $read?->first_read_at,

            'last_read_at' =>
                $read?->last_read_at,

            'read_count' =>
                $read?->read_count ?? 0,

            'is_acknowledged' =>
                $read?->acknowledged_at !== null,

            'acknowledged_at' =>
                $read?->acknowledged_at,

            'created_by' =>
                $notice->createdBy?->name,

            'attachments' =>
                $notice->attachments
                    ->map(
                        fn (
                            NoticeAttachment $attachment
                        ) => [
                            'id' =>
                                $attachment->id,

                            'original_name' =>
                                $attachment
                                    ->original_name,

                            'file_type' =>
                                $attachment
                                    ->file_type,

                            'formatted_size' =>
                                $attachment
                                    ->formatted_size,
                        ]
                    )
                    ->values(),

            'created_at' =>
                $notice->created_at,

            'updated_at' =>
                $notice->updated_at,
        ];
    }
}
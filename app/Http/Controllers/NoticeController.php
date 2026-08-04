<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Notice;
use App\Models\NoticeAttachment;
use App\Models\NoticeUpdate;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NoticeController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'all',
                    'draft',
                    'scheduled',
                    'published',
                    'expired',
                    'archived',
                ]),
            ],

            'priority' => [
                'nullable',
                Rule::in([
                    'all',
                    'normal',
                    'important',
                    'urgent',
                ]),
            ],

            'category' => [
                'nullable',
                Rule::in([
                    'all',
                    'general',
                    'academic',
                    'hostel',
                    'mess',
                    'maintenance',
                    'event',
                    'payment',
                    'emergency',
                    'policy',
                    'other',
                ]),
            ],
        ]);

        $filters = [
            'search' =>
                trim($validated['search'] ?? ''),

            'status' =>
                $validated['status'] ?? 'all',

            'priority' =>
                $validated['priority'] ?? 'all',

            'category' =>
                $validated['category'] ?? 'all',
        ];

        $this->expireNotices();

        $baseQuery = Notice::query();

        $notices = (clone $baseQuery)
            ->with([
                'buildings:id,name',

                'residents:id,first_name,last_name,resident_code',

                'attachments',

                'createdBy:id,name',
            ])
            ->withCount([
                'reads',
                'reads as acknowledged_count' =>
                    fn(Builder $query) =>
                    $query->whereNotNull(
                        'acknowledged_at'
                    ),
            ])
            ->when(
                $filters['search'] !== '',
                function (Builder $query) use ($filters): void {
                    $search = $filters['search'];

                    $query->where(function (Builder $query) use ($search): void {
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
                $filters['status'] !== 'all',
                fn(Builder $query) =>
                $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                $filters['priority'] !== 'all',
                fn(Builder $query) =>
                $query->where(
                    'priority',
                    $filters['priority']
                )
            )
            ->when(
                $filters['category'] !== 'all',
                fn(Builder $query) =>
                $query->where(
                    'category',
                    $filters['category']
                )
            )
            ->orderByDesc('is_pinned')
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(
                fn(Notice $notice) =>
                $this->transformNotice($notice)
            );

        return Inertia::render(
            'Notices/Index',
            [
                'notices' => $notices,
                'filters' => $filters,

                'stats' => [
                    'total' =>
                        (clone $baseQuery)->count(),

                    'draft' =>
                        (clone $baseQuery)
                            ->where('status', 'draft')
                            ->count(),

                    'scheduled' =>
                        (clone $baseQuery)
                            ->where('status', 'scheduled')
                            ->count(),

                    'published' =>
                        (clone $baseQuery)
                            ->where('status', 'published')
                            ->count(),

                    'expired' =>
                        (clone $baseQuery)
                            ->where('status', 'expired')
                            ->count(),

                    'archived' =>
                        (clone $baseQuery)
                            ->where('status', 'archived')
                            ->count(),
                ],

                'buildings' =>
                    Building::query()
                        ->orderBy('name')
                        ->get([
                            'id',
                            'name',
                        ]),

                'residents' =>
                    Resident::query()
                        ->whereIn('status', [
                            'active',
                            'upcoming',
                        ])
                        ->orderBy('first_name')
                        ->get([
                            'id',
                            'first_name',
                            'last_name',
                            'resident_code',
                        ])
                        ->map(
                            fn(Resident $resident) => [
                                'id' => $resident->id,

                                'name' => trim(
                                    $resident->first_name
                                    . ' '
                                    . $resident->last_name
                                ),

                                'resident_code' =>
                                    $resident->resident_code,
                            ]
                        ),
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate(
            $this->rules()
        );

        $notice = DB::transaction(
            function () use ($request, $validated): Notice {
                $status = $this->resolveStatus(
                    $validated['status'],
                    $validated['publish_at'] ?? null
                );

                $notice = Notice::create([
                    'title' =>
                        trim($validated['title']),

                    'summary' =>
                        filled($validated['summary'] ?? null)
                        ? trim($validated['summary'])
                        : null,

                    'content' =>
                        $validated['content'],

                    'category' =>
                        $validated['category'],

                    'priority' =>
                        $validated['priority'],

                    'status' =>
                        $status,

                    'audience_type' =>
                        $validated['audience_type'],

                    'is_pinned' =>
                        $validated['is_pinned'] ?? false,

                    'requires_acknowledgement' =>
                        $validated[
                            'requires_acknowledgement'
                        ] ?? false,

                    'publish_at' =>
                        $validated['publish_at']
                        ?? null,

                    'expires_at' =>
                        $validated['expires_at']
                        ?? null,

                    'published_at' =>
                        $status === 'published'
                        ? now()
                        : null,

                    'created_by' =>
                        $request->user()?->id,

                    'updated_by' =>
                        $request->user()?->id,
                ]);

                $this->syncAudience(
                    $notice,
                    $validated
                );

                $this->storeAttachments(
                    $request,
                    $notice
                );

                NoticeUpdate::create([
                    'notice_id' =>
                        $notice->id,

                    'action' =>
                        'created',

                    'old_status' =>
                        null,

                    'new_status' =>
                        $status,

                    'remarks' =>
                        'Notice created.',

                    'updated_by' =>
                        $request->user()?->id,
                ]);

                return $notice;
            }
        );

        return back()->with(
            'success',
            "Notice \"{$notice->title}\" created successfully."
        );
    }

    public function update(
        Request $request,
        Notice $notice
    ): RedirectResponse {
        $validated = $request->validate(
            $this->rules($notice)
        );

        DB::transaction(function () use ($request, $notice, $validated): void {
            $oldStatus = $notice->status;

            $status = $this->resolveStatus(
                $validated['status'],
                $validated['publish_at'] ?? null
            );

            $notice->update([
                'title' =>
                    trim($validated['title']),

                'summary' =>
                    filled($validated['summary'] ?? null)
                    ? trim($validated['summary'])
                    : null,

                'content' =>
                    $validated['content'],

                'category' =>
                    $validated['category'],

                'priority' =>
                    $validated['priority'],

                'status' =>
                    $status,

                'audience_type' =>
                    $validated['audience_type'],

                'is_pinned' =>
                    $validated['is_pinned'] ?? false,

                'requires_acknowledgement' =>
                    $validated[
                        'requires_acknowledgement'
                    ] ?? false,

                'publish_at' =>
                    $validated['publish_at']
                    ?? null,

                'expires_at' =>
                    $validated['expires_at']
                    ?? null,

                'published_at' =>
                    $status === 'published'
                    ? (
                        $notice->published_at
                        ?: now()
                    )
                    : $notice->published_at,

                'archived_at' =>
                    $status === 'archived'
                    ? now()
                    : null,

                'updated_by' =>
                    $request->user()?->id,
            ]);

            $this->syncAudience(
                $notice,
                $validated
            );

            $this->storeAttachments(
                $request,
                $notice
            );

            NoticeUpdate::create([
                'notice_id' =>
                    $notice->id,

                'action' =>
                    $oldStatus !== $status
                    ? 'status_changed'
                    : 'updated',

                'old_status' =>
                    $oldStatus,

                'new_status' =>
                    $status,

                'remarks' =>
                    $validated['update_remarks']
                    ?? 'Notice updated.',

                'updated_by' =>
                    $request->user()?->id,
            ]);
        });

        return back()->with(
            'success',
            'Notice updated successfully.'
        );
    }

    public function destroy(
        Notice $notice
    ): RedirectResponse {
        DB::transaction(
            function () use ($notice): void {
                foreach (
                    $notice->attachments
                    as $attachment
                ) {
                    Storage::disk('public')->delete(
                        $attachment->file_path
                    );
                }

                $notice->delete();
            }
        );

        return back()->with(
            'success',
            'Notice deleted successfully.'
        );
    }

    public function deleteAttachment(
        Notice $notice,
        NoticeAttachment $attachment
    ): RedirectResponse {
        abort_unless(
            (int) $attachment->notice_id ===
            (int) $notice->id,
            404
        );

        Storage::disk('public')->delete(
            $attachment->file_path
        );

        $attachment->delete();

        return back()->with(
            'success',
            'Attachment removed successfully.'
        );
    }

    private function rules(
        ?Notice $notice = null
    ): array {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'summary' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'content' => [
                'required',
                'string',
            ],

            'category' => [
                'required',
                Rule::in([
                    'general',
                    'academic',
                    'hostel',
                    'mess',
                    'maintenance',
                    'event',
                    'payment',
                    'emergency',
                    'policy',
                    'other',
                ]),
            ],

            'priority' => [
                'required',
                Rule::in([
                    'normal',
                    'important',
                    'urgent',
                ]),
            ],

            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'scheduled',
                    'published',
                    'archived',
                ]),
            ],

            'audience_type' => [
                'required',
                Rule::in([
                    'all',
                    'buildings',
                    'residents',
                ]),
            ],

            'building_ids' => [
                'nullable',
                'array',
                'required_if:audience_type,buildings',
            ],

            'building_ids.*' => [
                'integer',
                'exists:buildings,id',
            ],

            'resident_ids' => [
                'nullable',
                'array',
                'required_if:audience_type,residents',
            ],

            'resident_ids.*' => [
                'integer',
                'exists:residents,id',
            ],

            'is_pinned' => [
                'nullable',
                'boolean',
            ],

            'requires_acknowledgement' => [
                'nullable',
                'boolean',
            ],

            'publish_at' => [
                'nullable',
                'date',
            ],

            'expires_at' => [
                'nullable',
                'date',
                'after:publish_at',
            ],

            'attachments' => [
                'nullable',
                'array',
                'max:10',
            ],

            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            ],

            'update_remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    private function resolveStatus(
        string $requestedStatus,
        ?string $publishAt
    ): string {
        if (
            $requestedStatus === 'published'
            && $publishAt
            && now()->lt($publishAt)
        ) {
            return 'scheduled';
        }

        if (
            $requestedStatus === 'scheduled'
            && (
                !$publishAt
                || now()->gte($publishAt)
            )
        ) {
            return 'published';
        }

        return $requestedStatus;
    }

    private function syncAudience(
        Notice $notice,
        array $validated
    ): void {
        if (
            $validated['audience_type']
            === 'buildings'
        ) {
            $notice->buildings()->sync(
                $validated['building_ids']
                ?? []
            );

            $notice->residents()->detach();

            return;
        }

        if (
            $validated['audience_type']
            === 'residents'
        ) {
            $notice->residents()->sync(
                $validated['resident_ids']
                ?? []
            );

            $notice->buildings()->detach();

            return;
        }

        $notice->buildings()->detach();
        $notice->residents()->detach();
    }

    private function storeAttachments(
        Request $request,
        Notice $notice
    ): void {
        if (
            !$request->hasFile(
                'attachments'
            )
        ) {
            return;
        }

        foreach (
            $request->file('attachments')
            as $file
        ) {
            $path = $file->store(
                'notice-attachments',
                'public'
            );

            NoticeAttachment::create([
                'notice_id' =>
                    $notice->id,

                'file_path' =>
                    $path,

                'original_name' =>
                    $file->getClientOriginalName(),

                'file_type' =>
                    $file->getClientMimeType(),

                'file_size' =>
                    $file->getSize(),
            ]);
        }
    }

    private function expireNotices(): void
    {
        Notice::query()
            ->where('status', 'published')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update([
                'status' => 'expired',
            ]);

        Notice::query()
            ->where('status', 'scheduled')
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->update([
                'status' => 'published',
                'published_at' => now(),
            ]);
    }

    private function transformNotice(
        Notice $notice
    ): array {
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

            'status' => $notice->status,

            'status_label' =>
                $notice->status_label,

            'audience_type' =>
                $notice->audience_type,

            'is_pinned' =>
                $notice->is_pinned,

            'requires_acknowledgement' =>
                $notice
                    ->requires_acknowledgement,

            'publish_at' =>
                $notice->publish_at,

            'expires_at' =>
                $notice->expires_at,

            'published_at' =>
                $notice->published_at,

            'archived_at' =>
                $notice->archived_at,

            'buildings' =>
                $notice->buildings
                    ->map(
                        fn(Building $building) => [
                            'id' => $building->id,
                            'name' => $building->name,
                        ]
                    )
                    ->values(),

            'residents' =>
                $notice->residents
                    ->map(
                        fn(Resident $resident) => [
                            'id' => $resident->id,

                            'name' => trim(
                                $resident->first_name
                                . ' '
                                . $resident->last_name
                            ),

                            'resident_code' =>
                                $resident->resident_code,
                        ]
                    )
                    ->values(),

            'attachments' =>
                $notice->attachments
                    ->map(
                        fn(
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

                            'file_size' =>
                                $attachment
                                    ->file_size,

                            'formatted_size' =>
                                $attachment
                                    ->formatted_size,

                            'file_url' =>
                                $attachment
                                    ->file_url,
                        ]
                    )
                    ->values(),

            'read_count' =>
                $notice->reads_count ?? 0,

            'acknowledged_count' =>
                $notice->acknowledged_count
                ?? 0,

            'created_by' =>
                $notice->createdBy?->name,

            'created_at' =>
                $notice->created_at,

            'updated_at' =>
                $notice->updated_at,
        ];
    }
}
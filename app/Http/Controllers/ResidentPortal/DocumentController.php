<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\KycRequirement;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function index(
        Request $request
    ): Response {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'all',
                    'pending',
                    'verified',
                    'rejected',
                ]),
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

            'search' =>
                trim($validated['search'] ?? ''),
        ];

        /*
         * Active KYC requirements are shown to residents.
         * Inactive requirements are not uploadable.
         */
        $requirements = KycRequirement::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get([
                'id',
                'document_type',
                'label',
                'is_required',
                'is_active',
                'sort_order',
            ]);

        $allDocuments = Document::query()
            ->where(
                'resident_id',
                $resident->id
            )
            ->orderByDesc('uploaded_at')
            ->get();

        /*
         * Required/configured KYC documents are one per type.
         */
        $kycDocuments = $requirements
            ->map(function (KycRequirement $requirement) use ($allDocuments): array {
                $document = $allDocuments
                    ->first(
                        fn(Document $document) =>
                        $document->document_type
                        === $requirement->document_type
                    );

                return [
                    'id' =>
                        $requirement->id,

                    'document_type' =>
                        $requirement->document_type,

                    'label' =>
                        $requirement->label,

                    'is_required' =>
                        $requirement->is_required,

                    'sort_order' =>
                        $requirement->sort_order,

                    'document' =>
                        $document
                        ? $this->transformDocument(
                            $document
                        )
                        : null,
                ];
            })
            ->values();

        $additionalQuery = Document::query()
            ->where(
                'resident_id',
                $resident->id
            )
            ->where(
                'document_type',
                'other'
            )
            ->when(
                $filters['status'] !== 'all',
                fn(Builder $query) =>
                $query->where(
                    'verification_status',
                    $filters['status']
                )
            )
            ->when(
                $filters['search'] !== '',
                function (Builder $query) use ($filters): void {
                    $search = $filters['search'];

                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where(
                                'notes',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'file_name',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )
            ->orderByDesc('uploaded_at');

        $additionalDocuments = $additionalQuery
            ->paginate(12)
            ->withQueryString()
            ->through(
                fn(Document $document) =>
                $this->transformDocument(
                    $document
                )
            );

        $required = $kycDocuments
            ->where('is_required', true);

        $requiredCount = $required->count();

        $uploadedRequiredCount = $required
            ->filter(
                fn(array $item) =>
                $item['document'] !== null
            )
            ->count();

        $verifiedRequiredCount = $required
            ->filter(
                fn(array $item) =>
                data_get(
                    $item,
                    'document.verification_status'
                ) === 'verified'
            )
            ->count();

        $rejectedRequiredCount = $required
            ->filter(
                fn(array $item) =>
                data_get(
                    $item,
                    'document.verification_status'
                ) === 'rejected'
            )
            ->count();

        $pendingRequiredCount = $required
            ->filter(
                fn(array $item) =>
                data_get(
                    $item,
                    'document.verification_status'
                ) === 'pending'
            )
            ->count();

        $missingRequiredCount =
            max(
                $requiredCount
                - $uploadedRequiredCount,
                0
            );

        $kycStatus = match (true) {
            $requiredCount === 0 =>
            'complete',

            $verifiedRequiredCount === $requiredCount =>
            'complete',

            $uploadedRequiredCount < $requiredCount =>
            'incomplete',

            default =>
            'pending_verification',
        };

        return Inertia::render(
            'ResidentPortal/Documents/Index',
            [
                'kycDocuments' =>
                    $kycDocuments,

                'additionalDocuments' =>
                    $additionalDocuments,

                'requirements' =>
                    $requirements,

                'filters' =>
                    $filters,

                'stats' => [
                    'kyc_status' =>
                        $kycStatus,

                    'required_count' =>
                        $requiredCount,

                    'uploaded_required' =>
                        $uploadedRequiredCount,

                    'verified_required' =>
                        $verifiedRequiredCount,

                    'pending_required' =>
                        $pendingRequiredCount,

                    'rejected_required' =>
                        $rejectedRequiredCount,

                    'missing_required' =>
                        $missingRequiredCount,

                    'additional_count' =>
                        $allDocuments
                            ->where(
                                'document_type',
                                'other'
                            )
                            ->count(),

                    /*
                     * Progress represents verified required docs.
                     */
                    'completion_percentage' =>
                        $requiredCount > 0
                        ? round(
                            (
                                $verifiedRequiredCount
                                / $requiredCount
                            ) * 100
                        )
                        : 100,
                ],
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $allowedTypes = KycRequirement::query()
            ->where('is_active', true)
            ->pluck('document_type')
            ->push('other')
            ->unique()
            ->values()
            ->all();

        $validated = $request->validate([
            'document_type' => [
                'required',
                Rule::in($allowedTypes),
            ],

            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:8192',
            ],

            /*
             * For additional documents, notes is the title.
             */
            'notes' => [
                'nullable',
                'required_if:document_type,other',
                'string',
                'max:255',
            ],
        ], [
            'notes.required_if' =>
                'Please enter a title for the additional document.',
        ]);

        DB::transaction(function () use ($request, $resident, $validated): void {
            $file = $request->file('file');

            $path = $file->store(
                "documents/{$resident->id}",
                'public'
            );

            if ($validated['document_type'] === 'other') {
                Document::create([
                    'resident_id' =>
                        $resident->id,

                    'document_type' =>
                        'other',

                    'file_url' =>
                        Storage::disk('public')
                            ->url($path),

                    'file_name' =>
                        $file->getClientOriginalName(),

                    'verification_status' =>
                        'pending',

                    /*
                     * Custom title.
                     */
                    'notes' =>
                        trim($validated['notes']),

                    'uploaded_at' =>
                        now(),
                ]);

                return;
            }

            $existing = Document::query()
                ->where(
                    'resident_id',
                    $resident->id
                )
                ->where(
                    'document_type',
                    $validated['document_type']
                )
                ->first();

            if ($existing) {
                $this->deleteStoredFile($existing);

                $existing->update([
                    'file_url' =>
                        Storage::disk('public')
                            ->url($path),

                    'file_name' =>
                        $file->getClientOriginalName(),

                    'verification_status' =>
                        'pending',

                    /*
                     * Clear old rejection/admin note after replacement.
                     */
                    'notes' =>
                        null,

                    'uploaded_at' =>
                        now(),
                ]);

                return;
            }

            Document::create([
                'resident_id' =>
                    $resident->id,

                'document_type' =>
                    $validated['document_type'],

                'file_url' =>
                    Storage::disk('public')
                        ->url($path),

                'file_name' =>
                    $file->getClientOriginalName(),

                'verification_status' =>
                    'pending',

                'notes' =>
                    null,

                'uploaded_at' =>
                    now(),
            ]);
        });

        return back()->with(
            'success',
            $validated['document_type'] === 'other'
            ? 'Additional document uploaded successfully.'
            : 'Document uploaded and submitted for verification.'
        );
    }

    public function download(
        Document $document
    ): BinaryFileResponse {
        $document = $this
            ->residentDocumentOrFail(
                $document
            );

        $path = $document->storagePath();

        abort_unless(
            $path
            && Storage::disk('public')->exists($path),
            404
        );

        return response()->download(
            Storage::disk('public')->path($path),
            $document->file_name
            ?: basename($path)
        );
    }

    public function destroy(
        Document $document
    ): RedirectResponse {
        $document = $this
            ->residentDocumentOrFail(
                $document
            );

        /*
         * Only additional documents may be deleted.
         * KYC documents must be replaced rather than removed.
         */
        if ($document->document_type !== 'other') {
            return back()->with(
                'error',
                'KYC documents cannot be deleted. Upload a replacement instead.'
            );
        }

        $this->deleteStoredFile($document);

        $document->delete();

        return back()->with(
            'success',
            'Additional document removed.'
        );
    }

    private function residentDocumentOrFail(
        Document $document
    ): Document {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        abort_unless(
            (int) $document->resident_id
            === (int) $resident->id,
            403
        );

        return $document;
    }

    private function deleteStoredFile(
        Document $document
    ): void {
        $path = $document->storagePath();

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function transformDocument(
        Document $document
    ): array {
        return [
            'id' =>
                $document->id,

            'document_type' =>
                $document->document_type,

            'document_label' =>
                $document->document_label,

            'file_url' =>
                $document->file_url,

            'file_name' =>
                $document->file_name,

            'file_extension' =>
                $document->file_extension,

            'is_image' =>
                $document->is_image,

            'is_pdf' =>
                $document->is_pdf,

            'verification_status' =>
                $document->verification_status,

            /*
             * For KYC types this may contain admin remarks.
             * For "other" it contains the custom title.
             */
            'notes' =>
                $document->notes,

            'uploaded_at' =>
                $document->uploaded_at,
        ];
    }
}
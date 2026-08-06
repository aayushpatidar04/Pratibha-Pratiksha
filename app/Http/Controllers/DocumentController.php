<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\KycRequirement;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function store(
        Request $request,
        Resident $resident
    ): RedirectResponse {
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
             * For "other", notes acts as the custom document title.
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

            /*
             * Other documents can have multiple records.
             */
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

                    'notes' =>
                        trim($validated['notes']),

                    'uploaded_at' =>
                        now(),
                ]);

                return;
            }

            /*
             * KYC document types have one current document.
             * Re-upload replaces the old file.
             */
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

                    'notes' =>
                        $validated['notes'] ?? null,

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
                    $validated['notes'] ?? null,

                'uploaded_at' =>
                    now(),
            ]);
        });

        return back()->with(
            'success',
            $validated['document_type'] === 'other'
            ? 'Additional document uploaded.'
            : 'Document uploaded and submitted for verification.'
        );
    }

    public function updateStatus(
        Request $request,
        Document $document
    ): RedirectResponse {
        $validated = $request->validate([
            'verification_status' => [
                'required',
                'in:pending,verified,rejected',
            ],

            'notes' => [
                'nullable',
                'required_if:verification_status,rejected',
                'string',
                'max:1000',
            ],
        ], [
            'notes.required_if' =>
                'Please provide a rejection reason.',
        ]);

        $update = [
            'verification_status' =>
                $validated['verification_status'],
        ];

        /*
        * "Other" document titles are stored in notes,
        * so do not overwrite the title.
        */
        if ($document->document_type !== 'other') {
            $update['notes'] =
                $validated['notes'] ?? null;
        }

        $document->update($update);

        return back()->with(
            'success',
            match ($validated['verification_status']) {
                'verified' =>
                    'Document verified successfully.',

                'rejected' =>
                    'Document rejected. The resident can now upload a replacement.',

                default =>
                    'Document status reset to pending.',
            }
        );
    }

    public function download(
        Document $document
    ): BinaryFileResponse {
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
        $this->deleteStoredFile($document);

        $document->delete();

        return back()->with(
            'success',
            'Document removed.'
        );
    }

    private function deleteStoredFile(
        Document $document
    ): void {
        $path = $document->storagePath();

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
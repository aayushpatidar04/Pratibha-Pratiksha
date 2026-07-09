<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Upload (or re-upload) one document for a resident's KYC checklist.
     * Re-uploading the same document_type replaces the previous file and resets
     * verification back to pending, since the new file hasn't been checked yet.
     */
    public function store(Request $request, Resident $resident): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => 'required|in:aadhar_card,pan_card,photo,marksheet,caste_certificate,medical_certificate,parent_consent,other',
            'file' => 'required|file|max:8192',
            'notes' => 'nullable|string',
        ]);

        $existing = Document::where('resident_id', $resident->id)
            ->where('document_type', $validated['document_type'])
            ->first();

        if ($existing?->file_url) {
            Storage::disk('public')->delete(str_replace('/storage/', '', parse_url($existing->file_url, PHP_URL_PATH)));
        }

        $path = $request->file('file')->store('documents', 'public');

        Document::updateOrCreate(
            ['resident_id' => $resident->id, 'document_type' => $validated['document_type']],
            [
                'file_url' => Storage::disk('public')->url($path),
                'file_name' => $request->file('file')->getClientOriginalName(),
                'verification_status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'uploaded_at' => now(),
            ]
        );

        return back()->with('success', 'Document uploaded.');
    }

    public function updateStatus(Request $request, Document $document): RedirectResponse
    {
        $validated = $request->validate([
            'verification_status' => 'required|in:pending,verified,rejected',
            'notes' => 'nullable|string',
        ]);

        $document->update($validated);

        return back()->with('success', 'Document '.$validated['verification_status'].'.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        if ($document->file_url) {
            Storage::disk('public')->delete(str_replace('/storage/', '', parse_url($document->file_url, PHP_URL_PATH)));
        }
        $document->delete();

        return back()->with('success', 'Document removed.');
    }
}
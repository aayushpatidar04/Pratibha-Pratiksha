<?php

namespace App\Http\Controllers;

use App\Models\KycRequirement;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KycController extends Controller
{
    /**
     * Residents list with computed KYC status against whichever document types
     * are currently marked required in `kyc_requirements` (admin-controlled).
     */
    public function index(Request $request): Response
    {
        $requiredTypes = KycRequirement::where('is_required', true)->orderBy('sort_order')->pluck('document_type');

        $query = Resident::with('documents')->whereIn('status', ['active', 'upcoming']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('resident_code', 'like', "%{$search}%");
            });
        }

        $residents = $query->orderBy('first_name')->paginate(20)->withQueryString();

        $residents->getCollection()->transform(function ($resident) use ($requiredTypes) {
            $resident->kyc_status = $this->computeStatus($resident, $requiredTypes);
            return $resident;
        });

        $counts = [
            'complete' => 0,
            'pending_verification' => 0,
            'incomplete' => 0,
        ];
        Resident::with('documents')->whereIn('status', ['active', 'upcoming'])->get()->each(function ($r) use ($requiredTypes, &$counts) {
            $counts[$this->computeStatus($r, $requiredTypes)]++;
        });

        return Inertia::render('Residents/Kyc/Index', [
            'residents' => $residents,
            'requirements' => KycRequirement::where('is_required', true)->orderBy('sort_order')->get(),
            'allRequirements' => KycRequirement::orderBy('sort_order')->get(),
            'counts' => $counts,
            'filters' => $request->only('search'),
        ]);
    }

    protected function computeStatus(Resident $resident, $requiredTypes): string
    {
        if ($requiredTypes->isEmpty()) {
            return 'complete';
        }

        $docsByType = $resident->documents->keyBy('document_type');

        $allUploaded = $requiredTypes->every(fn($type) => $docsByType->has($type));
        if (!$allUploaded) {
            return 'incomplete';
        }

        $allVerified = $requiredTypes->every(fn($type) => $docsByType->get($type)?->verification_status === 'verified');

        return $allVerified ? 'complete' : 'pending_verification';
    }

    // ------------------------------------------------------------------
    // Admin-only: which document types are mandatory for KYC completion
    // ------------------------------------------------------------------
    public function settings(): Response
    {
        return Inertia::render('Residents/Kyc/Settings', [
            'requirements' => KycRequirement::orderBy('sort_order')->get(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requirements' => 'required|array',
            'requirements.*.id' => 'required|exists:kyc_requirements,id',
            'requirements.*.is_required' => 'required|boolean',
            'requirements.*.is_active' => 'required|boolean',
            'requirements.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['requirements'] as $row) {
            KycRequirement::where('id', $row['id'])->update([
                'is_required' => $row['is_required'],
                'is_active' => $row['is_active'],
                'sort_order' => $row['sort_order'],
            ]);
        }

        return back()->with('success', 'KYC requirements updated.');
    }

    public function storeRequirement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:100|unique:kyc_requirements,document_type',
            'label' => 'required|string|max:255',
            'is_required' => 'boolean',
        ]);

        KycRequirement::create([
            'document_type' => $validated['document_type'],
            'label' => $validated['label'],
            'is_required' => $validated['is_required'] ?? false,
            'is_active' => true,
            'sort_order' => KycRequirement::max('sort_order') + 1,
        ]);

        return back()->with('success', 'New KYC requirement added.');
    }

    public function destroyRequirement(KycRequirement $requirement): RedirectResponse
    {
        $requirement->delete();

        // Reorder remaining
        KycRequirement::orderBy('sort_order')->get()->each(function ($req, $index) {
            $req->update(['sort_order' => $index + 1]);
        });

        return back()->with('success', 'Requirement removed.');
    }
}
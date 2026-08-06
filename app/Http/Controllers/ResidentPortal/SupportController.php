<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function index(): Response
    {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $resident->loadMissing([
            'currentStay.building:id,name',
            'currentStay.room:id,room_number',
        ]);

        $recentRequests = Complaint::query()
            ->where('resident_id', $resident->id)
            ->where('category', 'other')
            ->latest('created_at')
            ->limit(5)
            ->get([
                'id',
                'title',
                'description',
                'priority',
                'status',
                'resolution_notes',
                'created_at',
                'resolved_at',
            ])
            ->map(fn (Complaint $complaint): array => [
                'id' => $complaint->id,
                'title' => $complaint->title,
                'description' => $complaint->description,
                'priority' => $complaint->priority,
                'status' => $complaint->status,
                'resolution_notes' =>
                    $complaint->resolution_notes,
                'created_at' => $complaint->created_at,
                'resolved_at' => $complaint->resolved_at,
            ]);

        return Inertia::render(
            'ResidentPortal/Support/Index',
            [
                'contact' => [
                    'hostel_name' =>
                        config('hostel.name'),

                    'office_phone' =>
                        config('hostel.office.phone'),

                    'office_whatsapp' =>
                        config('hostel.office.whatsapp'),

                    'office_email' =>
                        config('hostel.office.email'),

                    'office_address' =>
                        config('hostel.office.address'),

                    'office_timings' =>
                        config('hostel.office.timings'),

                    'reception_phone' =>
                        config('hostel.reception.phone'),

                    'warden_name' =>
                        config('hostel.warden.name'),

                    'warden_phone' =>
                        config('hostel.warden.phone'),

                    'warden_whatsapp' =>
                        config('hostel.warden.whatsapp'),

                    'emergency_phone' =>
                        config('hostel.emergency.phone'),
                ],

                'currentStay' =>
                    $resident->currentStay
                        ? [
                            'building_name' =>
                                $resident
                                    ->currentStay
                                    ->building?->name,

                            'room_number' =>
                                $resident
                                    ->currentStay
                                    ->room?->room_number,
                        ]
                        : null,

                'recentRequests' =>
                    $recentRequests,

                'faqs' => $this->faqs(),
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
            'subject' => [
                'required',
                'string',
                'max:200',
            ],

            'message' => [
                'required',
                'string',
                'max:5000',
            ],

            'priority' => [
                'required',
                'in:low,medium,high',
            ],
        ]);

        $resident->loadMissing('currentStay');

        Complaint::create([
            'resident_id' =>
                $resident->id,

            'building_id' =>
                $resident->currentStay?->building_id,

            'room_id' =>
                $resident->currentStay?->room_id,

            'category' => 'other',

            'priority' =>
                $validated['priority'],

            'title' =>
                trim($validated['subject']),

            'description' =>
                trim($validated['message']),

            'status' => 'open',
        ]);

        return back()->with(
            'success',
            'Your support request has been submitted.'
        );
    }

    private function faqs(): array
    {
        return [
            [
                'question' =>
                    'How can I download an invoice or receipt?',

                'answer' =>
                    'Open Billing or Payments from the resident portal. Select the required invoice or receipt and use the available English or Hindi download option.',
            ],

            [
                'question' =>
                    'How do I apply for leave?',

                'answer' =>
                    'Open the Leaves section, select Apply Leave, enter the dates and reason, and submit the request. Parent and administration approval status will be visible there.',
            ],

            [
                'question' =>
                    'How can I report a maintenance issue?',

                'answer' =>
                    'Open Complaints and raise a complaint using the relevant category such as electrical, plumbing, furniture, Wi-Fi, cleaning, food, or security.',
            ],

            [
                'question' =>
                    'How do I request a room change?',

                'answer' =>
                    'Open Room Change, select your preferred building, floor, room, and bed where available, enter the reason, and submit the request.',
            ],

            [
                'question' =>
                    'What should I do in an emergency?',

                'answer' =>
                    'Use Emergency SOS for immediate hostel assistance. For urgent danger, also contact the emergency or reception number shown on this page.',
            ],

            [
                'question' =>
                    'How can I update my personal details?',

                'answer' =>
                    'Open My Profile to update allowed contact, address, academic, and parent details. Identity-controlled information must be corrected by hostel administration.',
            ],

            [
                'question' =>
                    'Why is my KYC still pending?',

                'answer' =>
                    'Open My Documents and check whether every required document has been uploaded and verified. Rejected documents must be replaced with a clear and valid copy.',
            ],
        ];
    }
}
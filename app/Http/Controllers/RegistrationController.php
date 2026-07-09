<?php
// app/Http/Controllers/RegistrationController.php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\RegistrationApplication;
use App\Models\Resident;
use App\Models\Room;
use App\Models\Vehicle;
use App\Services\RoomAllotmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Razorpay\Api\Api;

class RegistrationController extends Controller
{
    /**
     * Show QR Code page for scanning
     */
    public function showQR()
    {
        $registrationUrl = route('register.form');

        // Generate QR code using simple-qrcode package
        // composer require simplesoftwareio/simple-qrcode
        $qrCode = null;
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)
                ->generate($registrationUrl);
        }

        return Inertia::render('Registration/QRCode', [
            'qrCode' => $qrCode ? base64_encode($qrCode) : null,
            'registrationUrl' => $registrationUrl,
        ]);
    }

    /**
     * Show the bilingual registration form
     */
    public function showForm(Request $request)
    {
        $lang = $request->get('lang', 'en');

        return Inertia::render('Registration/Form', [
            'lang' => in_array($lang, ['en', 'hi']) ? $lang : 'en',
            'razorpayKey' => config('services.razorpay.key'),
            'registrationFee' => (int) config('services.razorpay.registration_fee', 300),
        ]);
    }

    /**
     * Store registration application
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'mother_name' => 'required|string|max:255',
            'dob' => 'required|date|before:today',
            'age' => 'required|integer|min:10|max:100',
            'blood_group' => 'nullable|string|max:10',
            'student_mobile' => 'required|string|max:15',
            'father_mobile' => 'nullable|string|max:15',
            'mother_mobile' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
            'permanent_address' => 'required|string',
            'current_address' => 'nullable|string',
            'institution_name' => 'required|string|max:255',
            'institution_address' => 'nullable|string',
            'course_name' => 'required|string|max:255',
            'course_duration' => 'required|string|max:100',
            'room_type' => 'required|in:2-seater,3-seater,4-seater',
            'stay_duration_from' => 'nullable|date',
            'stay_duration_to' => 'nullable|date|after:stay_duration_from',
            'has_driving_license' => 'boolean',
            'vehicle_type' => 'nullable|in:two_wheeler,four_wheeler',
            'vehicle_number' => 'nullable|string|max:20',
            'disease_history' => 'nullable|string',
            'allergy_details' => 'nullable|string',
            'special_achievements' => 'nullable|string',
            'guardian1_name' => 'nullable|string|max:255',
            'guardian1_mobile' => 'nullable|string|max:15',
            'guardian1_occupation' => 'nullable|string|max:100',
            'guardian1_address' => 'nullable|string',
            'guardian2_name' => 'nullable|string|max:255',
            'guardian2_mobile' => 'nullable|string|max:15',
            'guardian2_occupation' => 'nullable|string|max:100',
            'guardian2_address' => 'nullable|string',
            'payment_method' => 'required|in:razorpay,cash',
            'razorpay_order_id' => 'nullable|string',
            'razorpay_payment_id' => 'nullable|string',
            'razorpay_signature' => 'nullable|string',
        ]);

        // Handle file uploads
        $files = ['student_photo', 'father_photo', 'mother_photo', 'family_photo1', 'family_photo2', 'guardian_photo'];
        foreach ($files as $file) {
            if ($request->hasFile($file)) {
                $validated[$file] = $request->file($file)->store('registration-photos', 'public');
            }
        }

        // Calculate age from DOB if not provided
        if (empty($validated['age']) && !empty($validated['dob'])) {
            $validated['age'] = now()->diffInYears($validated['dob']);
        }

        $validated['registration_fee'] = (int) config('services.razorpay.registration_fee', 300);

        $application = RegistrationApplication::create($validated);

        // If cash payment, mark as pending admin approval
        if ($validated['payment_method'] === 'cash') {
            return redirect()->route('register.success', $application)
                ->with('message', 'Application submitted. Please pay registration fee at the office.');
        }

        // For Razorpay, verify payment
        if ($validated['payment_method'] === 'razorpay') {
            try {
                $api = new Api(
                    config('services.razorpay.key'),
                    config('services.razorpay.secret')
                );

                $attributes = [
                    'razorpay_order_id' => $validated['razorpay_order_id'],
                    'razorpay_payment_id' => $validated['razorpay_payment_id'],
                    'razorpay_signature' => $validated['razorpay_signature'],
                ];

                $api->utility->verifyPaymentSignature($attributes);

                $application->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                return redirect()->route('register.success', $application)
                    ->with('message', 'Registration successful! Your application number is: ' . $application->application_no);

            } catch (\Exception $e) {
                $application->delete(); // Rollback on failure
                return back()->withErrors(['payment' => 'Payment verification failed: ' . $e->getMessage()]);
            }
        }

        return redirect()->route('register.success', $application);
    }

    /**
     * Success page after registration
     */
    public function success(RegistrationApplication $application)
    {
        return Inertia::render('Registration/Success', [
            'application' => $application,
        ]);
    }

    /**
     * Admin: List all registrations
     */
    public function index(Request $request): Response
    {
        $query = RegistrationApplication::query()->latest()->with('approvedBy');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                    ->orWhere('application_no', 'like', "%{$search}%")
                    ->orWhere('student_mobile', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $applications = $query->paginate(20)->withQueryString();

        $stats = [
            'pending' => RegistrationApplication::where('status', 'pending')->count(),
            'paid' => RegistrationApplication::where('status', 'paid')->count(),
            'approved' => RegistrationApplication::where('status', 'approved')->count(),
            'cash_pending' => RegistrationApplication::where('payment_method', 'cash')
                ->where('payment_status', 'pending_verification')->count(),
        ];

        return Inertia::render('Admin/Registrations/Index', [
            'applications' => $applications,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', 'all'),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Admin: Show single registration, plus everything needed to allot a room
     * right from this screen (buildings/floors/rooms/beds with amenities).
     */
    public function show(RegistrationApplication $registrationApplication): Response
    {
        $registrationApplication->load(['approvedBy', 'resident', 'allottedBuilding', 'allottedRoom', 'allottedBed']);

        return Inertia::render('Admin/Registrations/Show', [
            'application' => $registrationApplication,
            'buildings' => Building::orderBy('name')->get(['id', 'name']),
            'floors' => Floor::orderBy('floor_number')->get(['id', 'name', 'building_id']),
            'rooms' => Room::with('beds')->orderBy('room_number')->get([
                'id',
                'room_number',
                'room_type',
                'building_id',
                'floor_id',
                'capacity',
                'occupied_beds',
                'monthly_rent_per_bed',
                'has_ac',
                'has_wifi',
                'has_attached_bath',
                'has_balcony',
                'has_study_table',
            ]),
        ]);
    }

    /**
     * Admin: Approve registration — allots a real bed AND creates the Resident +
     * ResidentStay records from the application data, all in one transaction.
     * Can be called from pending, paid, OR rejected (a rejection isn't final —
     * re-approving afterwards is allowed).
     */
    public function approve(Request $request, RegistrationApplication $registrationApplication): RedirectResponse
    {
        if ($registrationApplication->status === 'approved') {
            return back()->with('error', 'This application has already been approved.');
        }

        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'floor_id' => 'required|exists:floors,id',
            'room_id' => 'required|exists:rooms,id',
            'bed_id' => 'required|exists:beds,id',
            'check_in_date' => 'nullable|date',
            'rent_amount' => 'nullable|numeric',
            'deposit_amount' => 'nullable|numeric',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($registrationApplication, $validated, $request) {
                // Reuse the resident if this application was somehow approved
                // once already (defensive — normally resident_id is null here).
                $resident = $registrationApplication->resident;

                if (!$resident) {
                    [$firstName, $lastName] = $this->splitName($registrationApplication->student_name);

                    $year = now()->year;
                    $seq = Resident::whereYear('created_at', $year)->count() + 1;

                    $resident = Resident::create([
                        'resident_code' => sprintf('PP-%d-%04d', $year, $seq),
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $registrationApplication->email,
                        'phone' => $registrationApplication->student_mobile,
                        'whatsapp_number' => $registrationApplication->student_mobile,
                        'date_of_birth' => $registrationApplication->dob,
                        'gender' => 'other', // not collected on the application form; editable afterwards
                        'blood_group' => $registrationApplication->blood_group,
                        'address' => $registrationApplication->permanent_address,
                        'country' => 'India',
                        'course' => $registrationApplication->course_name,
                        'institute' => $registrationApplication->institution_name,
                        'father_name' => $registrationApplication->father_name,
                        'father_phone' => $registrationApplication->father_mobile,
                        'mother_name' => $registrationApplication->mother_name,
                        'mother_phone' => $registrationApplication->mother_mobile,
                        'status' => 'upcoming',
                        'photo_url' => $registrationApplication->student_photo,
                        'created_by' => $request->user()?->id,
                    ]);
                }

                RoomAllotmentService::allot($resident, [
                    'building_id' => $validated['building_id'],
                    'floor_id' => $validated['floor_id'],
                    'room_id' => $validated['room_id'],
                    'bed_id' => $validated['bed_id'],
                    'check_in_date' => $validated['check_in_date'] ?? $registrationApplication->stay_duration_from,
                    'expected_check_out_date' => $registrationApplication->stay_duration_to,
                    'rent_amount' => $validated['rent_amount'] ?? null,
                    'deposit_amount' => $validated['deposit_amount'] ?? null,
                ]);

                if ($registrationApplication->vehicle_number) {
                    Vehicle::firstOrCreate(
                        ['resident_id' => $resident->id, 'vehicle_number' => $registrationApplication->vehicle_number],
                        ['vehicle_type' => $registrationApplication->vehicle_type ?: 'other']
                    );
                }

                $registrationApplication->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'admin_remarks' => $validated['remarks'] ?? $registrationApplication->admin_remarks,
                    'resident_id' => $resident->id,
                    'allotted_building_id' => $validated['building_id'],
                    'allotted_floor_id' => $validated['floor_id'],
                    'allotted_room_id' => $validated['room_id'],
                    'allotted_bed_id' => $validated['bed_id'],
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Application approved, resident created, and room allotted.');
    }

    /**
     * Admin: Reject registration. Not allowed once already approved (a resident
     * record and room allotment exist by then — undo that from the Residents
     * module instead, not by rejecting the application).
     */
    public function reject(Request $request, RegistrationApplication $registrationApplication): RedirectResponse
    {
        if ($registrationApplication->status === 'approved') {
            return back()->with('error', "This application is already approved and has a resident record — reject isn't available anymore.");
        }

        $validated = $request->validate(['remarks' => 'nullable|string']);

        $registrationApplication->update([
            'status' => 'rejected',
            'admin_remarks' => $validated['remarks'] ?? $registrationApplication->admin_remarks,
        ]);

        return back()->with('success', 'Application rejected.');
    }

    /**
     * Admin: Mark a cash payment as received. Only meaningful for cash applications
     * still awaiting verification.
     */
    public function markCashPaid(RegistrationApplication $registrationApplication): RedirectResponse
    {
        if ($registrationApplication->payment_method !== 'cash') {
            return back()->with('error', 'This application was not paid via cash.');
        }

        $registrationApplication->update([
            'status' => $registrationApplication->status === 'pending' ? 'paid' : $registrationApplication->status,
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Cash payment marked as received.');
    }

    protected function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [$parts[0] ?? $fullName, $parts[1] ?? ''];
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Building;
use App\Models\FeeInvoice;
use App\Models\Floor;
use App\Models\Payment;
use App\Models\Resident;
use App\Models\Room;
use App\Models\ResidentStay;
use App\Services\RoomAllotmentService;
use App\Services\SecurityDepositBillingService;
use App\Services\ShortStayBillingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ResidentController extends Controller
{
    public function index(Request $request): Response
    {
        $view = $request->string('view')->toString() ?: 'student';
        $sub = $request->string('sub')->toString() ?: 'active';

        return Inertia::render('Residents/Index', [
            'view' => $view,
            'tab' => $request->string('tab')->toString() ?: 'residents',
            'sub' => $request->string('sub')->toString() ?: 'active',
            'filters' => $request->only(
                'search',
                'gender',
                'course',
                'bookings_filter',
                'country',
                'state',
                'city',
                'living_up_to'
            ),
            'studentWise' => fn() => $view === 'student' ? $this->studentWiseData($request) : null,
            'hostelWise' => fn() => $view === 'hostel' ? $this->hostelWiseData() : null,
            'tabCounts' => fn() => [
                'active' => Resident::where('status', 'active')->count(),
                'upcoming_bookings' => Resident::where('status', 'upcoming')->count(),
                'left_suspended' => Resident::whereIn('status', ['left', 'suspended'])->count(),
            ],
            'buildings' => Building::orderBy('name')->get(['id', 'name']),
            'floors' => Floor::orderBy('floor_number')->get(['id', 'name', 'building_id']),
            'rooms' => Room::with('beds')->orderBy('room_number')->get(['id', 'room_number', 'building_id', 'floor_id', 'capacity', 'occupied_beds', 'monthly_rent_per_bed']),
        ]);
    }

    /**
     * "Student-Wise" tab: a filterable, paginated table — one row per resident.
     * Handles the three top-level tabs (Residents / Upcoming Bookings /
     * Left-Out-Fake-Suspended) and, within "Residents", the three sub-tabs
     * (Active Resident / Student List / New Joiners), plus the booking quick
     * filters and location/date filters shown in the filter bar.
     */
    protected function studentWiseData(Request $request): array
    {
        $tab = $request->string('tab')->toString() ?: 'residents';
        $sub = $request->string('sub')->toString() ?: 'active';

        $query = Resident::with(['currentStay.room', 'currentStay.building', 'currentStay.bed', 'currentStay.floor',]);

        match ($tab) {
            'upcoming_bookings' => $query->where('status', 'upcoming'),
            'left_suspended' => $query->whereIn('status', ['left', 'suspended']),
            default => match ($sub) {
                    'student_list' => $query->whereIn('status', ['active', 'upcoming']),
                    'new_joiners' => $query->where('status', 'active')
                        ->whereHas('currentStay', fn($q) => $q->where('check_in_date', '>=', now()->subDays(30))),
                    default => $query->where('status', 'active'),
                },
        };

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('resident_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%");
            });
        }
        if ($gender = $request->string('gender')->toString()) {
            $query->where('gender', $gender);
        }
        if ($course = $request->string('course')->toString()) {
            $query->where('course', 'like', "%{$course}%");
        }
        if ($country = $request->string('country')->toString()) {
            $query->where('country', $country);
        }
        if ($state = $request->string('state')->toString()) {
            $query->where('state', $state);
        }
        if ($city = $request->string('city')->toString()) {
            $query->where('city', $city);
        }

        $bookingsFilter = $request->string('bookings_filter')->toString();
        if ($bookingsFilter === 'no_living_end_date') {
            $query->whereHas('currentStay', fn($q) => $q->whereNull('expected_check_out_date'));
        } elseif ($bookingsFilter === 'leaving_7') {
            $query->whereHas('currentStay', fn($q) => $q->whereBetween('expected_check_out_date', [now(), now()->addDays(7)]));
        } elseif ($bookingsFilter === 'leaving_30') {
            $query->whereHas('currentStay', fn($q) => $q->whereBetween('expected_check_out_date', [now(), now()->addDays(30)]));
        }

        if ($livingUpTo = $request->date('living_up_to')) {
            $query->whereHas('currentStay', fn($q) => $q->whereDate('expected_check_out_date', '<=', $livingUpTo));
        }

        $bookingCounts = [
            'no_living_end_date' => (clone $query)->whereHas('currentStay', fn($q) => $q->whereNull('expected_check_out_date'))->count(),
            'leaving_7' => (clone $query)->whereHas('currentStay', fn($q) => $q->whereBetween('expected_check_out_date', [now(), now()->addDays(7)]))->count(),
            'leaving_30' => (clone $query)->whereHas('currentStay', fn($q) => $q->whereBetween('expected_check_out_date', [now(), now()->addDays(30)]))->count(),
        ];

        $residents = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return [
            'residents' => $residents,
            'bookingCounts' => $bookingCounts,
            'locationOptions' => [
                'countries' => Resident::whereNotNull('country')->distinct()->orderBy('country')->pluck('country'),
                'states' => Resident::whereNotNull('state')->distinct()->orderBy('state')->pluck('state'),
                'cities' => Resident::whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
            ],
        ];
    }

    /**
     * "Hostel-Wise" tab: Building → Floor → Room, one card per room, showing every
     * bed in that room and (if occupied) the resident's name/photo/gender.
     */
    protected function hostelWiseData(): array
    {
        $buildings = Building::with([
            'floors' => function ($q) {
                $q->orderBy('floor_number')->with([
                    'rooms' => function ($rq) {
                        $rq->orderBy('room_number')->with([
                            'beds' => function ($bq) {
                                $bq->with('resident:id,first_name,last_name,gender,photo_url,status');
                            }
                        ]);
                    }
                ]);
            }
        ])->orderBy('name')->get();

        return [
            'buildings' => $buildings->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'floors' => $b->floors->map(fn($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'rooms' => $f->rooms->map(fn($r) => [
                        'id' => $r->id,
                        'room_number' => $r->room_number,
                        'room_type' => $r->room_type,
                        'capacity' => $r->capacity,
                        'occupied_beds' => $r->occupied_beds,
                        'beds' => $r->beds->map(fn($bed) => [
                            'id' => $bed->id,
                            'bed_number' => $bed->bed_number,
                            'status' => $bed->status,
                            'resident' => $bed->resident ? [
                                'id' => $bed->resident->id,
                                'name' => trim("{$bed->resident->first_name} {$bed->resident->last_name}"),
                                'gender' => $bed->resident->gender,
                                'photo_url' => $bed->resident->photo_url,
                                'status' => $bed->resident->status,
                            ] : null,
                        ]),
                    ]),
                ]),
            ]),
        ];
    }

    /**
     * Past Residents: anyone whose most recent stay has actually ended (as opposed
     * to the `left`/`suspended` status filter, which is about the resident's admin
     * status rather than their occupancy history).
     */
    public function past(Request $request): Response
    {
        $query = Resident::with(['stays' => fn($q) => $q->where('status', 'ended')->with(['room', 'building'])->latest('actual_check_out_date')])
            ->whereHas('stays', fn($q) => $q->where('status', 'ended'));

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $residents = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return Inertia::render('Residents/Past', [
            'residents' => $residents,
            'filters' => $request->only('search'),
        ]);
    }

    protected function baseRules(bool $creating): array
    {
        return [
            'first_name' => ($creating ? 'required' : 'sometimes') . '|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:320',
            'phone' => ($creating ? 'required' : 'sometimes') . '|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => ($creating ? 'required' : 'sometimes') . '|in:male,female,other',
            'blood_group' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'course' => 'nullable|string|max:100',
            'year' => 'nullable|integer',
            'batch' => 'nullable|string|max:50',
            'roll_number' => 'nullable|string|max:50',
            'institute' => 'nullable|string|max:200',
            'father_name' => 'nullable|string|max:100',
            'father_phone' => 'nullable|string|max:20',
            'father_email' => 'nullable|email|max:320',
            'mother_name' => 'nullable|string|max:100',
            'mother_phone' => 'nullable|string|max:20',
            'status' => ($creating ? 'nullable' : 'sometimes') . '|in:active,inactive,suspended,left,upcoming',
            // Photograph is mandatory when creating a new resident (per requirement doc).
            'photo' => ($creating ? 'required' : 'nullable') . '|image|max:4096',
            // Optional room allotment at time of admission.
            'building_id' => 'nullable|exists:buildings,id',
            'floor_id' => 'nullable|exists:floors,id',
            'room_id' => 'nullable|exists:rooms,id',
            'bed_id' => 'nullable|exists:beds,id',
            'check_in_date' => 'nullable|date',
            'rent_amount' => 'nullable|numeric',
            'deposit_amount' => 'nullable|numeric',
            'billing_basis' => ['nullable', 'in:monthly,daily',],
            'daily_rate' => ['nullable', 'numeric', 'min:0', 'required_if:billing_basis,daily',],
            'expected_check_out_date' => ['nullable', 'date', 'after_or_equal:check_in_date', 'required_if:billing_basis,daily',],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            $this->baseRules(true)
        );

        $validated['whatsapp_number'] =
            $validated['whatsapp_number']
            ?? $validated['phone'];

        $validated['country'] =
            $validated['country']
            ?? 'India';

        $validated['status'] =
            $validated['status']
            ?? 'upcoming';

        $year = now()->year;

        $seq = Resident::whereYear(
            'created_at',
            $year
        )->count() + 1;

        $validated['resident_code'] = sprintf(
            'PP-%d-%04d',
            $year,
            $seq
        );

        $validated['created_by'] =
            $request->user()?->id;

        if ($request->hasFile('photo')) {
            $validated['photo_url'] = $request
                ->file('photo')
                ->store('residents', 'public');
        }

        $allotmentFields = [
            'building_id',
            'floor_id',
            'room_id',
            'bed_id',
            'check_in_date',
            'expected_check_out_date',
            'rent_amount',
            'deposit_amount',
            'billing_basis',
            'daily_rate',
        ];

        $allotment = collect($validated)
            ->only($allotmentFields)
            ->toArray();

        $residentData = collect($validated)
            ->except([
                ...$allotmentFields,
                'photo',
            ])
            ->toArray();

        DB::beginTransaction();

        try {
            $resident = Resident::create($residentData);

            if (!empty($allotment['bed_id'])) {
                RoomAllotmentService::allot(
                    $resident,
                    $allotment
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Resident could not be created: '
                    . $e->getMessage()
                );
        }

        return back()->with(
            'success',
            'Resident added successfully.'
        );
    }

    public function update(Request $request, Resident $resident): RedirectResponse
    {
        $validated = $request->validate($this->baseRules(false));

        if ($request->hasFile('photo')) {
            if ($resident->photo_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', parse_url($resident->photo_url, PHP_URL_PATH)));
            }
            $validated['photo_url'] = Storage::disk('public')->url(
                $request->file('photo')->store('residents', 'public')
            );
        }

        $residentData = collect($validated)->except(['building_id', 'floor_id', 'room_id', 'bed_id', 'check_in_date', 'rent_amount', 'deposit_amount', 'photo'])->toArray();

        $resident->update($residentData);

        return back()->with('success', 'Resident updated successfully.');
    }

    public function destroy(Resident $resident): RedirectResponse
    {
        $resident->delete();

        return back()->with('success', 'Resident deleted successfully.');
    }

    /**
     * Bulk-create residents from an uploaded CSV (headers: first_name,last_name,
     * phone,email,gender,course,institute,batch,year,roll_number,father_name,
     * father_phone). No photo/room allotment via this path — those still need to
     * be added individually afterwards (flagged by status staying "upcoming").
     */
    public function bulkUpload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240',
            ],
        ]);

        $file = $request->file('file');

        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return back()->with(
                'error',
                'Unable to read the uploaded CSV file.'
            );
        }

        $rawHeader = fgetcsv($handle);

        if (!$rawHeader) {
            fclose($handle);

            return back()->with(
                'error',
                'The CSV file does not contain a header row.'
            );
        }

        $header = array_map(
            fn($column) => $this->normalizeCsvHeader($column),
            $rawHeader
        );

        /*
         * Remove UTF-8 BOM from the first header.
         */
        if (isset($header[0])) {
            $header[0] = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $header[0]
            );
        }

        $requiredHeaders = [
            'first_name',
            'phone',
            'gender',
        ];

        $missingHeaders = array_values(
            array_diff($requiredHeaders, $header)
        );

        if ($missingHeaders !== []) {
            fclose($handle);

            return back()->withErrors([
                'file' => 'Missing required CSV columns: '
                    . implode(', ', $missingHeaders),
            ]);
        }

        $summary = [
            'processed' => 0,
            'residents_created' => 0,
            'residents_updated' => 0,
            'stays_created' => 0,
            'checkins_completed' => 0,
            'historical_stays_created' => 0,
            'deposit_invoices_created' => 0,
            'deposit_payments_created' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $failures = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $summary['processed']++;

            /*
             * Ignore completely blank lines.
             */
            if ($this->csvRowIsEmpty($row)) {
                $summary['skipped']++;
                continue;
            }

            /*
             * Normalize a row that has fewer or extra columns.
             */
            $row = array_pad($row, count($header), null);
            $row = array_slice($row, 0, count($header));

            $data = array_combine($header, $row);

            if ($data === false) {
                $summary['failed']++;

                $failures[] = [
                    'row' => $rowNumber,
                    'phone' => null,
                    'name' => null,
                    'reason' => 'CSV column count does not match the header.',
                ];

                continue;
            }

            $data = array_map(
                fn($value) => is_string($value)
                ? trim($value)
                : $value,
                $data
            );

            try {
                DB::transaction(function () use ($data, $request, &$summary) {
                    $normalized = $this->validateAndNormalizeBulkRow(
                        $data
                    );

                    /*
                     * Use phone as the primary duplicate key.
                     * Email is used as a fallback only when present.
                     */
                    $resident = Resident::query()
                        ->where('phone', $normalized['phone'])
                        ->when(
                            filled($normalized['email']),
                            function ($query) use ($normalized) {
                                $query->orWhere(
                                    'email',
                                    $normalized['email']
                                );
                            }
                        )
                        ->first();

                    $residentPayload = [
                        'first_name' =>
                            $normalized['first_name'],

                        'last_name' =>
                            $normalized['last_name'],

                        'email' =>
                            $normalized['email'],

                        'phone' =>
                            $normalized['phone'],

                        'whatsapp_number' =>
                            $normalized['whatsapp_number']
                            ?: $normalized['phone'],

                        'date_of_birth' =>
                            $normalized['date_of_birth'],

                        'gender' =>
                            $normalized['gender'],

                        'blood_group' =>
                            $normalized['blood_group'],

                        'address' =>
                            $normalized['address'],

                        'city' =>
                            $normalized['city'],

                        'state' =>
                            $normalized['state'],

                        'country' =>
                            $normalized['country'] ?: 'India',

                        'pincode' =>
                            $normalized['pincode'],

                        'course' =>
                            $normalized['course'],

                        'year' =>
                            $normalized['year'],

                        'batch' =>
                            $normalized['batch'],

                        'roll_number' =>
                            $normalized['roll_number'],

                        'institute' =>
                            $normalized['institute'],

                        'father_name' =>
                            $normalized['father_name'],

                        'father_phone' =>
                            $normalized['father_phone'],

                        'father_email' =>
                            $normalized['father_email'],

                        'mother_name' =>
                            $normalized['mother_name'],

                        'mother_phone' =>
                            $normalized['mother_phone'],

                        'status' =>
                            $normalized['resident_status'],
                    ];

                    if ($resident) {
                        $resident->update($residentPayload);
                        $summary['residents_updated']++;
                    } else {
                        $resident = Resident::create([
                            ...$residentPayload,

                            'resident_code' =>
                                $this->generateResidentCode(),

                            'created_by' =>
                                $request->user()?->id,
                        ]);

                        $summary['residents_created']++;
                    }

                    /*
                     * No room information means this is only a resident
                     * profile import.
                     */
                    if (!$normalized['has_stay_data']) {
                        return;
                    }

                    /*
                     * Prevent duplicate current stays on repeated uploads.
                     */
                    $existingCurrentStay = ResidentStay::query()
                        ->where('resident_id', $resident->id)
                        ->whereIn('status', [
                            'upcoming',
                            'active',
                        ])
                        ->first();

                    if (
                        $existingCurrentStay &&
                        in_array(
                            $normalized['stay_status'],
                            ['upcoming', 'active'],
                            true
                        )
                    ) {
                        throw new \RuntimeException(
                            "Resident already has current stay #{$existingCurrentStay->id}."
                        );
                    }

                    $building = $this->resolveBulkBuilding(
                        $normalized['building']
                    );

                    $room = $this->resolveBulkRoom(
                        $building,
                        $normalized['room_number']
                    );

                    $bed = $this->resolveBulkBed(
                        $room,
                        $normalized['bed_number'],
                        $normalized['stay_status']
                    );

                    $rentAmount = $normalized['rent_amount'];

                    if (
                        $normalized['billing_basis'] === 'monthly' &&
                        $rentAmount === null
                    ) {
                        $rentAmount = (float) (
                            $room->monthly_rent_per_bed ?? 0
                        );
                    }

                    /*
                     * A historical left resident must not occupy a bed or
                     * modify present room occupancy.
                     */
                    if ($normalized['stay_status'] === 'left') {
                        $stay = $this->createHistoricalCompletedStay(
                            resident: $resident,
                            building: $building,
                            room: $room,
                            bed: $bed,
                            data: $normalized,
                            rentAmount: $rentAmount,
                            userId: $request->user()?->id
                        );

                        $summary['stays_created']++;
                        $summary['historical_stays_created']++;
                    } else {
                        /*
                         * Upcoming or active current resident.
                         */
                        $stay = RoomAllotmentService::allot(
                            $resident,
                            [
                                'building_id' => $building->id,
                                'floor_id' => $room->floor_id,
                                'room_id' => $room->id,
                                'bed_id' => $bed->id,

                                'check_in_date' =>
                                    $normalized['check_in_date'],

                                'expected_check_out_date' =>
                                    $normalized[
                                        'expected_check_out_date'
                                    ],

                                'rent_amount' => $rentAmount,

                                'deposit_amount' =>
                                    $normalized['deposit_amount'],

                                'bill_type' =>
                                    $normalized['billing_basis'],

                                'billing_basis' =>
                                    $normalized['billing_basis'],

                                'daily_rate' =>
                                    $normalized['daily_rate'],

                                'notes' =>
                                    $normalized['stay_notes'],
                            ]
                        );

                        $summary['stays_created']++;

                        if (
                            $normalized['stay_status'] === 'active' ||
                            $normalized['check_in_status']
                        ) {
                            $stay = RoomAllotmentService::confirmCheckIn(
                                $stay,
                                [
                                    'check_in_date' =>
                                        $normalized[
                                            'check_in_date'
                                        ],

                                    /*
                                     * Historical CSV import does not assign
                                     * inventory automatically.
                                     */
                                    'inventory' => [],
                                ]
                            );

                            /*
                             * Preserve historical checked-in timestamp when
                             * supplied.
                             */
                            if ($normalized['checked_in_at']) {
                                $stay->update([
                                    'checked_in_at' =>
                                        $normalized[
                                            'checked_in_at'
                                        ],
                                ]);
                            }

                            $summary['checkins_completed']++;
                        }
                    }

                    /*
                     * Record historical deposit invoice and payment.
                     */
                    if ($normalized['deposit_amount'] > 0) {
                        $result =
                            $this->recordImportedDeposit(
                                stay: $stay,
                                data: $normalized,
                                userId: $request->user()?->id
                            );

                        if ($result['invoice_created']) {
                            $summary[
                                'deposit_invoices_created'
                            ]++;
                        }

                        if ($result['payment_created']) {
                            $summary[
                                'deposit_payments_created'
                            ]++;
                        }
                    }
                });
            } catch (Throwable $e) {
                report($e);

                $summary['failed']++;

                $failures[] = [
                    'row' => $rowNumber,
                    'phone' => $data['phone'] ?? null,
                    'name' => trim(
                        ($data['first_name'] ?? '')
                        . ' '
                        . ($data['last_name'] ?? '')
                    ),
                    'reason' => $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        /*
         * Store row failures temporarily in session so the UI can show them.
         */
        session()->flash(
            'bulk_upload_failures',
            array_slice($failures, 0, 100)
        );

        $message = sprintf(
            'Import complete: %d processed, %d residents created, %d residents updated, %d stays created, %d check-ins completed, %d deposit payments recorded, %d failed.',
            $summary['processed'],
            $summary['residents_created'],
            $summary['residents_updated'],
            $summary['stays_created'],
            $summary['checkins_completed'],
            $summary['deposit_payments_created'],
            $summary['failed']
        );

        return back()
            ->with('success', $message)
            ->with('bulk_upload_summary', $summary)
            ->with('bulk_upload_failures', array_slice($failures, 0, 100));
    }

    public function updateStayDates(
        Request $request,
        Resident $resident
    ): RedirectResponse {
        $stay = ResidentStay::query()
            ->where('resident_id', $resident->id)
            ->whereIn('status', ['upcoming', 'active'])
            ->latest('id')
            ->first();

        if (!$stay) {
            return back()->with(
                'error',
                'No active or upcoming stay was found for this resident.'
            );
        }

        $validated = $request->validate([
            'check_in_date' => [
                'required',
                'date',
            ],

            'expected_check_out_date' => [
                'nullable',
                'date',
                'after_or_equal:check_in_date',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        try {
            DB::transaction(function () use ($stay, $validated, $request) {
                $newCheckInDate = Carbon::parse(
                    $validated['check_in_date']
                )->startOfDay();

                $newExpectedCheckOutDate = !empty(
                    $validated['expected_check_out_date']
                )
                    ? Carbon::parse(
                        $validated['expected_check_out_date']
                    )->startOfDay()
                    : null;

                /*
                 * Completed stays should not be changed here.
                 */
                if (
                    $stay->actual_check_out_date ||
                    in_array(
                        $stay->status,
                        ['ended', 'completed', 'transferred'],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'check_in_date' =>
                            'Dates of a completed stay cannot be changed from this action.',
                    ]);
                }

                /*
                 * Daily stays need an expected checkout date because
                 * invoice amount depends on the number of days.
                 */
                if (
                    $stay->billing_basis === 'daily' &&
                    !$newExpectedCheckOutDate
                ) {
                    throw ValidationException::withMessages([
                        'expected_check_out_date' =>
                            'Expected checkout date is required for a daily stay.',
                    ]);
                }

                /*
                 * If already physically checked in, prevent moving
                 * check-in date into the future.
                 */
                if (
                    $stay->check_in_status &&
                    $newCheckInDate->gt(now()->startOfDay())
                ) {
                    throw ValidationException::withMessages([
                        'check_in_date' =>
                            'The check-in date cannot be in the future because this resident is already checked in.',
                    ]);
                }

                $oldCheckInDate = optional(
                    $stay->check_in_date
                )?->toDateString();

                $oldExpectedCheckOutDate = optional(
                    $stay->expected_check_out_date
                )?->toDateString();

                $reason = trim(
                    (string) ($validated['reason'] ?? '')
                );

                $dateChangeNote =
                    'Stay dates updated from check-in '
                    . ($oldCheckInDate ?: '-')
                    . ' / expected checkout '
                    . ($oldExpectedCheckOutDate ?: '-')
                    . ' to check-in '
                    . $newCheckInDate->toDateString()
                    . ' / expected checkout '
                    . (
                        $newExpectedCheckOutDate
                        ? $newExpectedCheckOutDate->toDateString()
                        : '-'
                    )
                    . '.';

                if ($reason !== '') {
                    $dateChangeNote .= ' Reason: ' . $reason;
                }

                $existingNotes = trim(
                    (string) ($stay->notes ?? '')
                );

                $stay->update([
                    'check_in_date' =>
                        $newCheckInDate->toDateString(),

                    'expected_check_out_date' =>
                        $newExpectedCheckOutDate
                        ? $newExpectedCheckOutDate->toDateString()
                        : null,

                    'notes' => trim(
                        $existingNotes
                        . ($existingNotes !== '' ? "\n" : '')
                        . '['
                        . now()->format('d-m-Y h:i A')
                        . '] '
                        . $dateChangeNote
                        . ' Updated by user #'
                        . ($request->user()?->id ?? '-')
                    ),
                ]);

                /*
                 * Recalculate the same short-stay invoice only when
                 * the resident has physically checked in.
                 *
                 * Before actual check-in, no short-stay invoice should
                 * exist in the revised workflow.
                 */
                if (
                    $stay->billing_basis === 'daily' &&
                    $stay->check_in_status
                ) {
                    app(ShortStayBillingService::class)
                        ->createOrUpdateInvoice(
                            $stay->fresh()
                        );
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\RuntimeException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }

        return back()->with(
            'success',
            'Stay dates updated successfully.'
        );
    }

    private function validateAndNormalizeBulkRow(array $data): array
    {
        $gender = strtolower(
            trim((string) ($data['gender'] ?? ''))
        );

        $residentStatus = strtolower(
            trim((string) ($data['status'] ?? 'upcoming'))
        );

        $stayStatus = strtolower(
            trim(
                (string) (
                    $data['stay_status']
                    ?? $residentStatus
                )
            )
        );

        $billingBasis = strtolower(
            trim(
                (string) (
                    $data['billing_basis']
                    ?? 'monthly'
                )
            )
        );

        $normalized = [
            /*
             * Resident fields
             */
            'first_name' =>
                $this->nullableString(
                    $data['first_name'] ?? null
                ),

            'last_name' =>
                $this->nullableString(
                    $data['last_name'] ?? null
                ),

            'email' =>
                $this->nullableString(
                    $data['email'] ?? null
                ),

            'phone' =>
                $this->normalizePhone(
                    $data['phone'] ?? null
                ),

            'whatsapp_number' =>
                $this->normalizePhone(
                    $data['whatsapp_number'] ?? null
                ),

            'date_of_birth' =>
                $this->normalizeCsvDate(
                    $data['date_of_birth'] ?? null
                ),

            'gender' => $gender,

            'blood_group' =>
                $this->nullableString(
                    $data['blood_group'] ?? null
                ),

            'address' =>
                $this->nullableString(
                    $data['address'] ?? null
                ),

            'city' =>
                $this->nullableString(
                    $data['city'] ?? null
                ),

            'state' =>
                $this->nullableString(
                    $data['state'] ?? null
                ),

            'country' =>
                $this->nullableString(
                    $data['country'] ?? 'India'
                ),

            'pincode' =>
                $this->nullableString(
                    $data['pincode'] ?? null
                ),

            'course' =>
                $this->nullableString(
                    $data['course'] ?? null
                ),

            'year' =>
                $this->nullableInteger(
                    $data['year'] ?? null
                ),

            'batch' =>
                $this->nullableString(
                    $data['batch'] ?? null
                ),

            'roll_number' =>
                $this->nullableString(
                    $data['roll_number'] ?? null
                ),

            'institute' =>
                $this->nullableString(
                    $data['institute'] ?? null
                ),

            'father_name' =>
                $this->nullableString(
                    $data['father_name'] ?? null
                ),

            'father_phone' =>
                $this->normalizePhone(
                    $data['father_phone'] ?? null
                ),

            'father_email' =>
                $this->nullableString(
                    $data['father_email'] ?? null
                ),

            'mother_name' =>
                $this->nullableString(
                    $data['mother_name'] ?? null
                ),

            'mother_phone' =>
                $this->normalizePhone(
                    $data['mother_phone'] ?? null
                ),

            'resident_status' => $residentStatus,

            /*
             * Stay fields
             */
            'building' =>
                $this->nullableString(
                    $data['building'] ??
                    $data['building_no'] ??
                    null
                ),

            'room_number' =>
                $this->nullableString(
                    $data['room_number'] ??
                    $data['room_no'] ??
                    null
                ),

            'bed_number' =>
                $this->nullableString(
                    $data['bed_number'] ??
                    $data['bed_no'] ??
                    null
                ),

            'check_in_date' =>
                $this->normalizeCsvDate(
                    $data['check_in_date'] ?? null
                ),

            'expected_check_out_date' =>
                $this->normalizeCsvDate(
                    $data[
                        'expected_check_out_date'
                    ] ??
                    $data['checkout_date'] ??
                    null
                ),

            'actual_check_out_date' =>
                $this->normalizeCsvDate(
                    $data[
                        'actual_check_out_date'
                    ] ?? null
                ),

            'checked_in_at' =>
                $this->normalizeCsvDateTime(
                    $data['checked_in_at'] ?? null
                ),

            'check_in_status' =>
                $this->normalizeBoolean(
                    $data['check_in_status']
                    ?? $data['checked_in']
                    ?? null,
                    in_array(
                        $stayStatus,
                        ['active', 'left'],
                        true
                    )
                ),

            'stay_status' => $stayStatus,

            'billing_basis' => $billingBasis,

            'rent_amount' =>
                $this->nullableFloat(
                    $data['rent_amount'] ?? null
                ),

            'daily_rate' =>
                $this->nullableFloat(
                    $data['daily_rate'] ?? null
                ),

            'deposit_amount' =>
                $this->nullableFloat(
                    $data['deposit_amount'] ?? 0
                ) ?? 0,

            'stay_notes' =>
                $this->nullableString(
                    $data['stay_notes'] ?? null
                ),

            /*
             * Deposit payment fields
             */
            'deposit_payment_date' =>
                $this->normalizeCsvDate(
                    $data[
                        'deposit_payment_date'
                    ] ??
                    $data['payment_date'] ??
                    null
                ),

            'deposit_payment_mode' =>
                strtolower(
                    trim(
                        (string) (
                            $data[
                                'deposit_payment_mode'
                            ] ?? 'cash'
                        )
                    )
                ),

            'deposit_reference' =>
                $this->nullableString(
                    $data['deposit_reference'] ?? null
                ),

            'deposit_notes' =>
                $this->nullableString(
                    $data['deposit_notes'] ?? null
                ),
        ];

        $normalized['has_stay_data'] =
            filled($normalized['building']) ||
            filled($normalized['room_number']) ||
            filled($normalized['bed_number']) ||
            filled($normalized['check_in_date']) ||
            filled($normalized['expected_check_out_date']) ||
            filled($normalized['actual_check_out_date']);

        $validator = Validator::make(
            $normalized,
            [
                'first_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'phone' => [
                    'required',
                    'string',
                    'max:30',
                ],

                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                ],

                'gender' => [
                    'required',
                    Rule::in([
                        'male',
                        'female',
                        'other',
                    ]),
                ],

                'resident_status' => [
                    'required',
                    Rule::in([
                        'active',
                        'inactive',
                        'suspended',
                        'left',
                        'upcoming',
                    ]),
                ],

                'stay_status' => [
                    'required',
                    Rule::in([
                        'active',
                        'upcoming',
                        'ended',
                        'transferred',
                    ]),
                ],

                'billing_basis' => [
                    'required',
                    Rule::in([
                        'monthly',
                        'daily',
                    ]),
                ],

                'rent_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'daily_rate' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'deposit_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],
            ]
        );

        $validator->after(
            function ($validator) use ($normalized) {
                if (!$normalized['has_stay_data']) {
                    return;
                }

                foreach (
                    [
                        'building',
                        'room_number',
                        'bed_number',
                        'check_in_date',
                    ] as $field
                ) {
                    if (blank($normalized[$field])) {
                        $validator->errors()->add(
                            $field,
                            "{$field} is required when stay data is supplied."
                        );
                    }
                }

                if (
                    $normalized['stay_status'] === 'left' &&
                    blank(
                        $normalized[
                            'actual_check_out_date'
                        ]
                    )
                ) {
                    $validator->errors()->add(
                        'actual_check_out_date',
                        'Actual checkout date is required for left residents.'
                    );
                }

                if (
                    $normalized['billing_basis'] === 'daily' &&
                    empty($normalized['daily_rate'])
                ) {
                    $validator->errors()->add(
                        'daily_rate',
                        'Daily rate is required for daily stays.'
                    );
                }

                if (
                    $normalized['billing_basis'] === 'daily' &&
                    blank(
                        $normalized[
                            'expected_check_out_date'
                        ]
                    ) &&
                    blank(
                        $normalized[
                            'actual_check_out_date'
                        ]
                    )
                ) {
                    $validator->errors()->add(
                        'expected_check_out_date',
                        'Checkout date is required for daily stays.'
                    );
                }

                if (
                    $normalized['deposit_amount'] > 0 &&
                    blank(
                        $normalized[
                            'deposit_payment_date'
                        ]
                    )
                ) {
                    $validator->errors()->add(
                        'deposit_payment_date',
                        'Deposit payment date is required when a deposit amount is supplied.'
                    );
                }

                if (
                    $normalized['check_in_date'] &&
                    $normalized[
                        'expected_check_out_date'
                    ] &&
                    Carbon::parse(
                        $normalized[
                            'expected_check_out_date'
                        ]
                    )->lt(
                            Carbon::parse(
                                $normalized['check_in_date']
                            )
                        )
                ) {
                    $validator->errors()->add(
                        'expected_check_out_date',
                        'Expected checkout date cannot be before check-in date.'
                    );
                }

                if (
                    $normalized['check_in_date'] &&
                    $normalized[
                        'actual_check_out_date'
                    ] &&
                    Carbon::parse(
                        $normalized[
                            'actual_check_out_date'
                        ]
                    )->lt(
                            Carbon::parse(
                                $normalized['check_in_date']
                            )
                        )
                ) {
                    $validator->errors()->add(
                        'actual_check_out_date',
                        'Actual checkout date cannot be before check-in date.'
                    );
                }
            }
        );

        $validator->validate();

        return $normalized;
    }

    private function resolveBulkBuilding(
        string $buildingValue
    ): Building {
        $building = Building::query()
            ->whereRaw(
                'LOWER(TRIM(name)) = ?',
                [strtolower(trim($buildingValue))]
            )
            ->first();

        if (!$building) {
            throw new \RuntimeException(
                "Building '{$buildingValue}' was not found."
            );
        }

        return $building;
    }

    private function resolveBulkRoom(
        Building $building,
        string $roomNumber
    ): Room {
        $room = Room::query()
            ->where('building_id', $building->id)
            ->whereRaw(
                'LOWER(TRIM(room_number)) = ?',
                [strtolower(trim($roomNumber))]
            )
            ->first();

        if (!$room) {
            throw new \RuntimeException(
                "Room '{$roomNumber}' was not found in {$building->name}."
            );
        }

        return $room;
    }

    private function resolveBulkBed(
        Room $room,
        string $bedNumber,
        string $stayStatus
    ): Bed {
        $bed = Bed::query()
            ->where('room_id', $room->id)
            ->whereRaw(
                'LOWER(TRIM(bed_number)) = ?',
                [strtolower(trim($bedNumber))]
            )
            ->first();

        if (!$bed) {
            throw new \RuntimeException(
                "Bed '{$bedNumber}' was not found in room {$room->room_number}."
            );
        }

        /*
         * Historical left residents do not modify present occupancy,
         * therefore their old bed may currently be occupied.
         */
        if (
            $stayStatus !== 'left' &&
            (
                $bed->status === 'occupied' ||
                filled($bed->resident_id)
            )
        ) {
            throw new \RuntimeException(
                "Bed '{$bedNumber}' is currently occupied."
            );
        }

        return $bed;
    }

    private function createHistoricalCompletedStay(
        Resident $resident,
        Building $building,
        Room $room,
        Bed $bed,
        array $data,
        float $rentAmount,
        ?int $userId
    ): ResidentStay {
        $checkedInAt = $data['checked_in_at']
            ?: Carbon::parse(
                $data['check_in_date']
            )->startOfDay();

        $stay = ResidentStay::create([
            'resident_id' => $resident->id,
            'building_id' => $building->id,
            'floor_id' => $room->floor_id,
            'room_id' => $room->id,
            'bed_id' => $bed->id,

            'check_in_date' =>
                $data['check_in_date'],

            'expected_check_out_date' =>
                $data['expected_check_out_date'],

            'actual_check_out_date' =>
                $data['actual_check_out_date'],

            'rent_amount' => $rentAmount,

            'deposit_amount' =>
                $data['deposit_amount'],

            'bill_type' =>
                $data['billing_basis'],

            'billing_basis' =>
                $data['billing_basis'],

            'daily_rate' =>
                $data['daily_rate'],

            'status' => 'ended',

            'notes' =>
                $data['stay_notes']
                ?: 'Historical stay imported through CSV.',

            'check_in_status' => true,

            'checked_in_at' => $checkedInAt,

            'checked_in_by' => $userId,

            'checkout_status' => 'approved',

            'checkout_notes' =>
                'Historical checkout imported through CSV.',

            'checkout_reviewed_by' => $userId,

            'checkout_reviewed_at' =>
                Carbon::parse(
                    $data['actual_check_out_date']
                )->endOfDay(),
        ]);

        $resident->update([
            'status' => 'left',
        ]);

        return $stay;
    }

    private function recordImportedDeposit(
        ResidentStay $stay,
        array $data,
        ?int $userId
    ): array {
        $invoiceAlreadyExisted = FeeInvoice::query()
            ->where('resident_id', $stay->resident_id)
            ->where('stay_id', $stay->id)
            ->where('fee_type', 'security_deposit')
            ->exists();

        $invoice = app(
            SecurityDepositBillingService::class
        )->createInvoice($stay);

        if (!$invoice) {
            return [
                'invoice_created' => false,
                'payment_created' => false,
            ];
        }

        /*
         * Avoid duplicate payment when the same CSV is re-uploaded.
         */
        $paymentAlreadyExists = Payment::query()
            ->where('invoice_id', $invoice->id)
            ->whereDate(
                'payment_date',
                $data['deposit_payment_date']
            )
            ->where(
                'amount',
                $data['deposit_amount']
            )
            ->exists();

        if ($paymentAlreadyExists) {
            return [
                'invoice_created' =>
                    !$invoiceAlreadyExisted,

                'payment_created' => false,
            ];
        }

        /*
         * Adjust the field names below only if your payments table uses
         * different column names.
         */
        Payment::create([
            'invoice_id' => $invoice->id,
            'resident_id' => $stay->resident_id,

            'amount' => $data['deposit_amount'],

            'payment_date' =>
                $data['deposit_payment_date'],

            'payment_mode' =>
                $data['deposit_payment_mode'],

            'transaction_id' =>
                $data['deposit_reference'],

            'notes' =>
                $data['deposit_notes']
                ?: 'Historical security deposit imported through CSV.',

            'status' => 'completed',

        ]);

        $paidAmount = (float) $invoice
            ->payments()
            ->sum('amount');

        $invoice->update([
            'paid_amount' => $paidAmount,
            'status' => $paidAmount >= (float) $invoice->amount
                ? 'paid'
                : 'partial',
        ]);

        return [
            'invoice_created' => !$invoiceAlreadyExisted,
            'payment_created' => true,
        ];
    }

    private function normalizeCsvHeader(
        mixed $header
    ): string {
        $header = strtolower(
            trim((string) $header)
        );

        $header = preg_replace(
            '/[^a-z0-9]+/',
            '_',
            $header
        );

        return trim($header, '_');
    }

    private function csvRowIsEmpty(array $row): bool
    {
        return collect($row)->every(
            fn($value) => blank(trim((string) $value))
        );
    }

    private function nullableString(
        mixed $value
    ): ?string {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInteger(
        mixed $value
    ): ?int {
        if (blank($value)) {
            return null;
        }

        return (int) $value;
    }

    private function nullableFloat(
        mixed $value
    ): ?float {
        if (blank($value)) {
            return null;
        }

        $value = str_replace(
            [',', '₹'],
            '',
            trim((string) $value)
        );

        if (!is_numeric($value)) {
            throw new \RuntimeException(
                "Invalid numeric value '{$value}'."
            );
        }

        return round((float) $value, 2);
    }

    private function normalizePhone(
        mixed $value
    ): ?string {
        if (blank($value)) {
            return null;
        }

        /*
         * Preserve leading + but remove spaces, dashes and brackets.
         */
        $phone = preg_replace(
            '/[^\d+]/',
            '',
            trim((string) $value)
        );

        return $phone ?: null;
    }

    private function normalizeBoolean(
        mixed $value,
        bool $default = false
    ): bool {
        if (blank($value)) {
            return $default;
        }

        return in_array(
            strtolower(trim((string) $value)),
            [
                '1',
                'yes',
                'y',
                'true',
                'checked_in',
                'active',
            ],
            true
        );
    }

    private function normalizeCsvDate(
        mixed $value
    ): ?string {
        if (blank($value)) {
            return null;
        }

        $value = trim((string) $value);

        foreach (
            [
                'Y-m-d',
                'd-m-Y',
                'd/m/Y',
                'm/d/Y',
                'd.m.Y',
            ] as $format
        ) {
            try {
                $date = Carbon::createFromFormat(
                    $format,
                    $value
                );

                if (
                    $date &&
                    $date->format($format) === $value
                ) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
                // Try the next format.
            }
        }

        try {
            return Carbon::parse($value)
                ->toDateString();
        } catch (Throwable) {
            throw new \RuntimeException(
                "Invalid date '{$value}'. Use YYYY-MM-DD."
            );
        }
    }

    private function normalizeCsvDateTime(
        mixed $value
    ): ?string {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse(
                trim((string) $value)
            )->toDateTimeString();
        } catch (Throwable) {
            throw new \RuntimeException(
                "Invalid date/time '{$value}'."
            );
        }
    }

    private function generateResidentCode(): string
    {
        $year = now()->year;
        $prefix = "PP-{$year}-";

        $lastResident = Resident::query()
            ->where(
                'resident_code',
                'like',
                "{$prefix}%"
            )
            ->lockForUpdate()
            ->orderByDesc('resident_code')
            ->first();

        $sequence = 1;

        if ($lastResident) {
            $parts = explode(
                '-',
                $lastResident->resident_code
            );

            $sequence =
                ((int) end($parts)) + 1;
        }

        return sprintf(
            'PP-%d-%04d',
            $year,
            $sequence
        );
    }

    public function downloadBulkCsvTemplate(): StreamedResponse
    {
        $headers = $this->residentBulkUploadHeaders();

        $sampleRow = $this->residentBulkUploadSampleRow();

        $fileName = 'resident-bulk-upload-template.csv';

        return response()->streamDownload(
            function () use ($headers, $sampleRow) {
                $handle = fopen('php://output', 'w');

                /*
                * UTF-8 BOM so Excel correctly displays Hindi or other
                * Unicode characters.
                */
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, $headers);
                fputcsv($handle, $sampleRow);

                fclose($handle);
            },
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    public function downloadBulkExcelTemplate()
    {
        $headers = $this->residentBulkUploadHeaders();
        $sampleRow = $this->residentBulkUploadSampleRow();

        $spreadsheet = new Spreadsheet();

        /*
        * Residents sheet
        */
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Residents');

        foreach ($headers as $index => $header) {
            $column = $this->excelColumnName($index + 1);

            $sheet->setCellValue("{$column}1", $header);
            $sheet->setCellValueExplicit(
                "{$column}2",
                (string) ($sampleRow[$index] ?? ''),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            $sheet->getColumnDimension($column)
                ->setWidth(
                    in_array($header, [
                        'address',
                        'deposit_notes',
                        'stay_notes',
                    ], true)
                        ? 35
                        : 20
                );
        }

        $lastColumn = $this->excelColumnName(count($headers));

        $sheet->getStyle("A1:{$lastColumn}1")
            ->getFont()
            ->setBold(true)
            ->setColor(
                new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF')
            );

        $sheet->getStyle("A1:{$lastColumn}1")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF4F46E5');

        $sheet->getStyle("A1:{$lastColumn}1")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");

        /*
        * Highlight required columns.
        */
        $requiredColumns = [
            'first_name',
            'phone',
            'gender',
        ];

        foreach ($headers as $index => $header) {
            if (!in_array($header, $requiredColumns, true)) {
                continue;
            }

            $column = $this->excelColumnName($index + 1);

            $sheet->getStyle("{$column}1")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFDC2626');
        }

        /*
        * Dropdown validations.
        */
        $this->addExcelDropdown(
            $sheet,
            $headers,
            'gender',
            ['male', 'female', 'other']
        );

        $this->addExcelDropdown(
            $sheet,
            $headers,
            'status',
            [
                'active',
                'inactive',
                'suspended',
                'left',
                'upcoming',
            ]
        );

        $this->addExcelDropdown(
            $sheet,
            $headers,
            'stay_status',
            [
                'active',
                'upcoming',
                'left',
            ]
        );

        $this->addExcelDropdown(
            $sheet,
            $headers,
            'check_in_status',
            [
                'yes',
                'no',
            ]
        );

        $this->addExcelDropdown(
            $sheet,
            $headers,
            'billing_basis',
            [
                'monthly',
                'daily',
            ]
        );

        $this->addExcelDropdown(
            $sheet,
            $headers,
            'deposit_payment_mode',
            [
                'cash',
                'upi',
                'bank_transfer',
                'cheque',
                'online',
            ]
        );

        /*
        * Instructions sheet.
        */
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Instructions');

        $instructionSheet->fromArray([
            [
                'Column',
                'Required',
                'Example',
                'Description',
            ],
            [
                'first_name',
                'Yes',
                'Rahul',
                'Resident first name.',
            ],
            [
                'phone',
                'Yes',
                '9876543210',
                'Primary resident phone number.',
            ],
            [
                'gender',
                'Yes',
                'male',
                'Allowed: male, female, other.',
            ],
            [
                'status',
                'No',
                'active',
                'Allowed: active, inactive, suspended, left, upcoming.',
            ],
            [
                'building',
                'For stay',
                'Main Building',
                'Must match an existing building.',
            ],
            [
                'room_number',
                'For stay',
                '201',
                'Must match an existing room in the selected building.',
            ],
            [
                'bed_number',
                'For stay',
                'B1',
                'Must match an existing bed in the selected room.',
            ],
            [
                'stay_status',
                'For stay',
                'active',
                'Allowed: active, upcoming, left.',
            ],
            [
                'check_in_date',
                'For stay',
                '2025-07-01',
                'Use YYYY-MM-DD.',
            ],
            [
                'expected_check_out_date',
                'No',
                '2026-06-30',
                'Expected checkout date in YYYY-MM-DD.',
            ],
            [
                'actual_check_out_date',
                'For left residents',
                '2026-05-15',
                'Required when stay_status is left.',
            ],
            [
                'billing_basis',
                'No',
                'monthly',
                'Allowed: monthly or daily.',
            ],
            [
                'rent_amount',
                'Monthly stay',
                '6500',
                'Monthly rent amount.',
            ],
            [
                'daily_rate',
                'Daily stay',
                '350',
                'Required when billing_basis is daily.',
            ],
            [
                'deposit_amount',
                'No',
                '5000',
                'Refundable security deposit amount.',
            ],
            [
                'deposit_payment_date',
                'When deposit exists',
                '2025-07-01',
                'Required when deposit_amount is greater than zero.',
            ],
            [
                'deposit_payment_mode',
                'When deposit exists',
                'cash',
                'Cash, UPI, bank transfer, cheque or online.',
            ],
        ]);

        $instructionSheet
            ->getStyle('A1:D1')
            ->getFont()
            ->setBold(true)
            ->setColor(
                new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF')
            );

        $instructionSheet
            ->getStyle('A1:D1')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF4F46E5');

        $instructionSheet->getColumnDimension('A')->setWidth(32);
        $instructionSheet->getColumnDimension('B')->setWidth(20);
        $instructionSheet->getColumnDimension('C')->setWidth(25);
        $instructionSheet->getColumnDimension('D')->setWidth(65);

        $instructionSheet->getStyle('A1:D30')
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);

        /*
        * Save and download.
        */
        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'resident_import_'
        );

        $writer = new Xlsx($spreadsheet);
        $writer->save($temporaryFile);

        $spreadsheet->disconnectWorksheets();

        return response()
            ->download(
                $temporaryFile,
                'resident-bulk-upload-template.xlsx',
                [
                    'Content-Type' =>
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )
            ->deleteFileAfterSend(true);
    }

    private function residentBulkUploadHeaders(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'phone',
            'whatsapp_number',
            'date_of_birth',
            'gender',
            'blood_group',
            'address',
            'city',
            'state',
            'country',
            'pincode',
            'course',
            'year',
            'batch',
            'roll_number',
            'institute',
            'father_name',
            'father_phone',
            'father_email',
            'mother_name',
            'mother_phone',
            'status',

            'building',
            'room_number',
            'bed_number',
            'stay_status',
            'check_in_date',
            'expected_check_out_date',
            'actual_check_out_date',
            'check_in_status',
            'checked_in_at',
            'billing_basis',
            'rent_amount',
            'daily_rate',
            'deposit_amount',
            'deposit_payment_date',
            'deposit_payment_mode',
            'deposit_reference',
            'deposit_notes',
            'stay_notes',
        ];
    }

    private function residentBulkUploadSampleRow(): array
    {
        return [
            'Rahul',
            'Sharma',
            'rahul@example.com',
            '9876543210',
            '9876543210',
            '2003-05-12',
            'male',
            'B+',
            'Neemuch Road',
            'Chhoti Sadri',
            'Rajasthan',
            'India',
            '312604',
            'B.Tech',
            '2',
            '2025',
            'RN101',
            'ABC College',
            'Ramesh Sharma',
            '9876500000',
            'ramesh@example.com',
            'Sunita Sharma',
            '9876500001',
            'active',

            'Main Building',
            '201',
            'B1',
            'active',
            '2025-07-01',
            '2026-06-30',
            '',
            'yes',
            '2025-07-01 10:30:00',
            'monthly',
            '6500',
            '',
            '5000',
            '2025-07-01',
            'cash',
            'DEP-001',
            'Historical security deposit',
            'Imported from old hostel records',
        ];
    }

    private function addExcelDropdown(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $headers,
        string $field,
        array $options
    ): void {
        $index = array_search($field, $headers, true);

        if ($index === false) {
            return;
        }

        $column = $this->excelColumnName($index + 1);

        /*
        * Add validation to rows 2 through 1000.
        */
        for ($row = 2; $row <= 1000; $row++) {
            $validation = $sheet
                ->getCell("{$column}{$row}")
                ->getDataValidation();

            $validation->setType(
                DataValidation::TYPE_LIST
            );

            $validation->setErrorStyle(
                DataValidation::STYLE_STOP
            );

            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Invalid value');
            $validation->setError(
                'Please select a value from the list.'
            );

            $validation->setFormula1(
                '"' . implode(',', $options) . '"'
            );
        }
    }

    private function excelColumnName(int $number): string
    {
        $column = '';

        while ($number > 0) {
            $number--;

            $column =
                chr(65 + ($number % 26))
                . $column;

            $number = intdiv($number, 26);
        }

        return $column;
    }

}
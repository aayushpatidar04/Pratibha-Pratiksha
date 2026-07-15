<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Resident;
use App\Models\Room;
use App\Models\ResidentStay;
use App\Services\RoomAllotmentService;
use App\Services\ShortStayBillingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

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
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $created = 0;
        $skipped = 0;
        $year = now()->year;
        $seq = Resident::whereYear('created_at', $year)->count();

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            if (empty($data['first_name']) || empty($data['phone']) || empty($data['gender'])) {
                $skipped++;
                continue;
            }

            $seq++;
            Resident::create([
                'resident_code' => sprintf('PP-%d-%04d', $year, $seq),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'],
                'whatsapp_number' => $data['phone'],
                'email' => $data['email'] ?? null,
                'gender' => strtolower($data['gender']),
                'course' => $data['course'] ?? null,
                'institute' => $data['institute'] ?? null,
                'batch' => $data['batch'] ?? null,
                'year' => $data['year'] ?? null,
                'roll_number' => $data['roll_number'] ?? null,
                'father_name' => $data['father_name'] ?? null,
                'father_phone' => $data['father_phone'] ?? null,
                'country' => 'India',
                'status' => 'upcoming',
                'created_by' => $request->user()?->id,
            ]);
            $created++;
        }
        fclose($handle);

        return back()->with('success', "Bulk upload complete: {$created} residents created, {$skipped} rows skipped.");
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
}
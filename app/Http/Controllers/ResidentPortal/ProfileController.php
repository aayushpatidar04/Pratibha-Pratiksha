<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\ResidentStay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the resident profile, current stay,
     * registration information and account details.
     */
    public function index(): Response
    {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $resident->load([
            'currentStay.building:id,name',
            'currentStay.floor:id,name,floor_number',
            'currentStay.room:id,room_number,room_type',
            'currentStay.bed:id,bed_number',

            'latestRegistrationApplication' => function ($query) {
                $query->addSelect([
                    'registration_applications.id',
                    'registration_applications.resident_id',
                    'registration_applications.application_no',
                    'registration_applications.status',

                    'registration_applications.student_name',
                    'registration_applications.father_name',
                    'registration_applications.mother_name',
                    'registration_applications.dob',
                    'registration_applications.age',
                    'registration_applications.blood_group',
                    'registration_applications.student_photo',
                    'registration_applications.student_mobile',
                    'registration_applications.father_mobile',
                    'registration_applications.mother_mobile',
                    'registration_applications.email',

                    'registration_applications.permanent_address',
                    'registration_applications.current_address',

                    'registration_applications.institution_name',
                    'registration_applications.institution_address',
                    'registration_applications.course_name',
                    'registration_applications.course_duration',

                    'registration_applications.room_type',
                    'registration_applications.stay_duration_from',
                    'registration_applications.stay_duration_to',

                    'registration_applications.has_driving_license',
                    'registration_applications.vehicle_type',
                    'registration_applications.vehicle_number',

                    'registration_applications.disease_history',
                    'registration_applications.allergy_details',
                    'registration_applications.special_achievements',

                    'registration_applications.guardian1_name',
                    'registration_applications.guardian1_mobile',
                    'registration_applications.guardian1_occupation',
                    'registration_applications.guardian1_address',

                    'registration_applications.guardian2_name',
                    'registration_applications.guardian2_mobile',
                    'registration_applications.guardian2_occupation',
                    'registration_applications.guardian2_address',

                    'registration_applications.father_photo',
                    'registration_applications.mother_photo',
                    'registration_applications.family_photo1',
                    'registration_applications.family_photo2',
                    'registration_applications.guardian_photo',

                    'registration_applications.approved_at',
                    'registration_applications.admin_remarks',
                    'registration_applications.created_at',
                ]);
            },

            'vehicles' => function ($query) {
                $query->latest('id');
            },
        ]);

        $application =
            $resident->latestRegistrationApplication;

        $currentStay = $resident->currentStay;

        return Inertia::render(
            'ResidentPortal/Profile/Index',
            [
                'resident' => [
                    'id' => $resident->id,

                    'resident_code' =>
                        $resident->resident_code,

                    'first_name' =>
                        $resident->first_name,

                    'last_name' =>
                        $resident->last_name,

                    'full_name' =>
                        $resident->full_name,

                    'email' =>
                        $resident->email,

                    'phone' =>
                        $resident->phone,

                    'whatsapp_number' =>
                        $resident->whatsapp_number,

                    'date_of_birth' =>
                        optional(
                            $resident->date_of_birth
                        )?->toDateString(),

                    'gender' =>
                        $resident->gender,

                    'aadhar_number' =>
                        $resident->aadhar_number,

                    'blood_group' =>
                        $resident->blood_group,

                    'address' =>
                        $resident->address,

                    'city' =>
                        $resident->city,

                    'state' =>
                        $resident->state,

                    'country' =>
                        $resident->country,

                    'pincode' =>
                        $resident->pincode,

                    'course' =>
                        $resident->course,

                    'year' =>
                        $resident->year,

                    'batch' =>
                        $resident->batch,

                    'roll_number' =>
                        $resident->roll_number,

                    'institute' =>
                        $resident->institute,

                    'father_name' =>
                        $resident->father_name,

                    'father_phone' =>
                        $resident->father_phone,

                    'father_email' =>
                        $resident->father_email,

                    'mother_name' =>
                        $resident->mother_name,

                    'mother_phone' =>
                        $resident->mother_phone,

                    'status' =>
                        $resident->status,

                    'portal_enabled' =>
                        $resident->portal_enabled,

                    'photo_url' =>
                        $this->publicFileUrl(
                            $resident->photo_url
                        ),

                    'last_login_at' =>
                        $resident->last_login_at,

                    'password_changed_at' =>
                        $resident->password_changed_at,

                    'must_change_password' =>
                        $resident->must_change_password,

                    'created_at' =>
                        $resident->created_at,

                    'updated_at' =>
                        $resident->updated_at,
                ],

                'currentStay' => $currentStay
                    ? [
                        'id' =>
                            $currentStay->id,

                        'status' =>
                            $currentStay->status,

                        'building_id' =>
                            $currentStay->building_id,

                        'building_name' =>
                            $currentStay
                                ->building?->name,

                        'floor_id' =>
                            $currentStay->floor_id,

                        'floor_name' =>
                            $currentStay
                                ->floor?->name,

                        'floor_number' =>
                            $currentStay
                                ->floor?->floor_number,

                        'room_id' =>
                            $currentStay->room_id,

                        'room_number' =>
                            $currentStay
                                ->room?->room_number,

                        'room_type' =>
                            $currentStay
                                ->room?->room_type,

                        'bed_id' =>
                            $currentStay->bed_id,

                        'bed_number' =>
                            $currentStay
                                ->bed?->bed_number,

                        'check_in_date' =>
                            optional(
                                $currentStay
                                    ->check_in_date
                            )?->toDateString(),

                        'expected_check_out_date' =>
                            optional(
                                $currentStay
                                    ->expected_check_out_date
                            )?->toDateString(),

                        'actual_check_out_date' =>
                            optional(
                                $currentStay
                                    ->actual_check_out_date
                            )?->toDateString(),

                        'billing_basis' =>
                            $currentStay
                                ->billing_basis,

                        'rent_amount' =>
                            $currentStay
                                ->rent_amount,

                        'daily_rate' =>
                            $currentStay
                                ->daily_rate,

                        'deposit_amount' =>
                            $currentStay
                                ->deposit_amount,

                        'check_in_status' =>
                            (bool) $currentStay
                                ->check_in_status,

                        'checked_in_at' =>
                            $currentStay
                                ->checked_in_at,
                    ]
                    : null,

                'registrationApplication' =>
                    $application
                    ? [
                        'id' =>
                            $application->id,

                        'application_no' =>
                            $application
                                ->application_no,

                        'status' =>
                            $application->status,

                        'institution_address' =>
                            $application
                                ->institution_address,

                        'course_duration' =>
                            $application
                                ->course_duration,

                        'room_type' =>
                            $application
                                ->room_type,

                        'stay_duration_from' =>
                            optional(
                                $application
                                    ->stay_duration_from
                            )?->toDateString(),

                        'stay_duration_to' =>
                            optional(
                                $application
                                    ->stay_duration_to
                            )?->toDateString(),

                        'has_driving_license' =>
                            (bool) $application
                                ->has_driving_license,

                        'vehicle_type' =>
                            $application
                                ->vehicle_type,

                        'vehicle_number' =>
                            $application
                                ->vehicle_number,

                        'disease_history' =>
                            $application
                                ->disease_history,

                        'allergy_details' =>
                            $application
                                ->allergy_details,

                        'special_achievements' =>
                            $application
                                ->special_achievements,

                        'guardian1_name' =>
                            $application
                                ->guardian1_name,

                        'guardian1_mobile' =>
                            $application
                                ->guardian1_mobile,

                        'guardian1_occupation' =>
                            $application
                                ->guardian1_occupation,

                        'guardian1_address' =>
                            $application
                                ->guardian1_address,

                        'guardian2_name' =>
                            $application
                                ->guardian2_name,

                        'guardian2_mobile' =>
                            $application
                                ->guardian2_mobile,

                        'guardian2_occupation' =>
                            $application
                                ->guardian2_occupation,

                        'guardian2_address' =>
                            $application
                                ->guardian2_address,

                        'student_photo_url' =>
                            $this->publicFileUrl(
                                $application
                                    ->student_photo
                            ),

                        'father_photo_url' =>
                            $this->publicFileUrl(
                                $application
                                    ->father_photo
                            ),

                        'mother_photo_url' =>
                            $this->publicFileUrl(
                                $application
                                    ->mother_photo
                            ),

                        'family_photo1_url' =>
                            $this->publicFileUrl(
                                $application
                                    ->family_photo1
                            ),

                        'family_photo2_url' =>
                            $this->publicFileUrl(
                                $application
                                    ->family_photo2
                            ),

                        'guardian_photo_url' =>
                            $this->publicFileUrl(
                                $application
                                    ->guardian_photo
                            ),

                        'approved_at' =>
                            $application
                                ->approved_at,

                        'admin_remarks' =>
                            $application
                                ->admin_remarks,
                    ]
                    : null,

                'vehicles' =>
                    $resident->vehicles
                        ->map(function ($vehicle) {
                            return [
                                'id' =>
                                    $vehicle->id,

                                'vehicle_type' =>
                                    $vehicle
                                        ->vehicle_type,

                                'vehicle_number' =>
                                    $vehicle
                                        ->vehicle_number,

                                'make_model' =>
                                    $vehicle
                                        ->make_model
                                    ?? null,

                                'color' =>
                                    $vehicle->color
                                    ?? null,

                                'parking_slot' =>
                                    $vehicle
                                        ->parking_slot
                                    ?? null,

                                'status' =>
                                    $vehicle->status
                                    ?? null,

                                'created_at' =>
                                    $vehicle
                                        ->created_at,
                            ];
                        })
                        ->values(),

                /*
                 * Explicitly tell the Vue page which
                 * fields the resident can edit.
                 */
                'editableFields' => [
                    'email',
                    'whatsapp_number',

                    'address',
                    'city',
                    'state',
                    'country',
                    'pincode',

                    'institute',
                    'course',
                    'year',
                    'batch',
                    'roll_number',

                    'father_name',
                    'father_phone',
                    'father_email',

                    'mother_name',
                    'mother_phone',

                    'expected_check_out_date' =>
                        optional(
                            $currentStay
                                ->expected_check_out_date
                        )?->toDateString(),
                ],

                'readOnlyFields' => [
                    'resident_code',
                    'first_name',
                    'last_name',
                    'phone',
                    'date_of_birth',
                    'gender',
                    'aadhar_number',
                    'blood_group',
                    'status',
                    'portal_enabled',
                ],
            ]
        );
    }

    /**
     * Update fields the resident is permitted
     * to change directly.
     */
    public function update(
        Request $request
    ): RedirectResponse {
        /** @var Resident|null $resident */
        $resident =
            Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'email' => [
                'nullable',
                'email',
                'max:320',
            ],

            'whatsapp_number' => [
                'nullable',
                'string',
                'max:20',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'country' => [
                'nullable',
                'string',
                'max:100',
            ],

            'pincode' => [
                'nullable',
                'string',
                'max:10',
            ],

            'institute' => [
                'nullable',
                'string',
                'max:200',
            ],

            'course' => [
                'nullable',
                'string',
                'max:100',
            ],

            'year' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],

            'batch' => [
                'nullable',
                'string',
                'max:50',
            ],

            'roll_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'father_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'father_phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'father_email' => [
                'nullable',
                'email',
                'max:320',
            ],

            'mother_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'mother_phone' => [
                'nullable',
                'string',
                'max:20',
            ],
        ]);

        $validated = collect($validated)
            ->map(function ($value) {
                if (!is_string($value)) {
                    return $value;
                }

                $value = trim($value);

                return $value === ''
                    ? null
                    : $value;
            })
            ->toArray();

        if (
            empty($validated['country'])
        ) {
            $validated['country'] = 'India';
        }

        $resident->update($validated);

        /*
         * Keep matching registration information
         * synchronized where it already exists.
         *
         * This is useful because profile printing may
         * also use registration application fields.
         */
        $application =
            $resident
                ->latestRegistrationApplication()
                ->first();

        if ($application) {
            $application->update([
                'email' =>
                    $validated['email']
                    ?? $application->email,

                'current_address' =>
                    $validated['address']
                    ?? $application
                        ->current_address,

                'institution_name' =>
                    $validated['institute']
                    ?? $application
                        ->institution_name,

                'course_name' =>
                    $validated['course']
                    ?? $application
                        ->course_name,

                'father_name' =>
                    $validated['father_name']
                    ?? $application
                        ->father_name,

                'father_mobile' =>
                    $validated['father_phone']
                    ?? $application
                        ->father_mobile,

                'mother_name' =>
                    $validated['mother_name']
                    ?? $application
                        ->mother_name,

                'mother_mobile' =>
                    $validated['mother_phone']
                    ?? $application
                        ->mother_mobile,
            ]);
        }

        return back()->with(
            'success',
            'Profile information updated successfully.'
        );
    }

    /**
     * Replace the resident's profile photograph.
     */
    public function updatePhoto(
        Request $request
    ): RedirectResponse {
        /** @var Resident|null $resident */
        $resident =
            Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
        ]);

        $oldPath = $this->storagePathFromValue(
            $resident->photo_url
        );

        $newPath = $request
            ->file('photo')
            ->store(
                "residents/{$resident->id}",
                'public'
            );

        $resident->update([
            /*
             * Keep the same path convention as the
             * resident creation process.
             */
            'photo_url' => $newPath,
        ]);

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete(
                $oldPath
            );
        }

        return back()->with(
            'success',
            'Profile photo updated successfully.'
        );
    }

    /**
     * Update the resident portal password.
     */
    public function updatePassword(
        Request $request
    ): RedirectResponse {
        /** @var Resident|null $resident */
        $resident =
            Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],
        ]);

        if (
            !Hash::check(
                $validated['current_password'],
                $resident->password
            )
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'current_password' =>
                    'The current password is incorrect.',
            ]);
        }

        if (
            Hash::check(
                $validated['password'],
                $resident->password
            )
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'password' =>
                    'The new password must be different from the current password.',
            ]);
        }

        $resident->update([
            'password' =>
                $validated['password'],

            'password_changed_at' =>
                now(),

            'must_change_password' =>
                false,
        ]);

        return back()->with(
            'success',
            'Password changed successfully.'
        );
    }

    private function publicFileUrl(
        ?string $value
    ): ?string {
        if (!$value) {
            return null;
        }

        if (
            str_starts_with(
                $value,
                'http://'
            )
            || str_starts_with(
                $value,
                'https://'
            )
            || str_starts_with(
                $value,
                '/storage/'
            )
        ) {
            return $value;
        }

        return Storage::disk('public')->url(
            ltrim($value, '/')
        );
    }

    private function storagePathFromValue(
        ?string $value
    ): ?string {
        if (!$value) {
            return null;
        }

        $path = parse_url(
            $value,
            PHP_URL_PATH
        );

        if (!$path) {
            return null;
        }

        return ltrim(
            str_replace(
                '/storage/',
                '',
                $path
            ),
            '/'
        );
    }

    public function updateExpectedCheckoutDate(Request $request, ResidentStay $stay): RedirectResponse
    {
        $validated = $request->validate([
                'expected_check_out_date' => [
                    'nullable', 'date', 'after_or_equal:' . $stay->check_in_date->toDateString(),
                ],
            ]);
        if ($stay->actual_check_out_date || !in_array($stay->status, ['active', 'upcoming'], true)) {
            return back()->with('error', 'The expected checkout date cannot be changed for this stay.');
        }
        $stay->update(['expected_check_out_date' => $validated['expected_check_out_date'] ?? null,]);

        return back()->with('success', 'Expected checkout date updated successfully.');
    }
}
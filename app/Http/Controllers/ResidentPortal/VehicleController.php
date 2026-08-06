<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VehicleController extends Controller
{
    public function index(): Response
    {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $vehicles = Vehicle::query()
            ->where(
                'resident_id',
                $resident->id
            )
            ->orderByDesc('id')
            ->get()
            ->map(
                fn(Vehicle $vehicle) => [
                    'id' =>
                        $vehicle->id,

                    'vehicle_type' =>
                        $vehicle->vehicle_type,

                    'vehicle_number' =>
                        $vehicle->vehicle_number,

                    'color' =>
                        $vehicle->color,

                    'model' =>
                        $vehicle->model,

                    'rc_file_url' =>
                        $vehicle->rc_file_public_url,

                    'rc_file_extension' =>
                        $vehicle->rc_file_extension,

                    'rc_is_image' =>
                        $vehicle->rc_is_image,

                    'rc_is_pdf' =>
                        $vehicle->rc_is_pdf,

                    'created_at' =>
                        $vehicle->created_at,
                ]
            )
            ->values();

        return Inertia::render(
            'ResidentPortal/Vehicles/Index',
            [
                'vehicles' => $vehicles,

                'stats' => [
                    'total' =>
                        $vehicles->count(),

                    'two_wheeler' =>
                        $vehicles
                            ->where(
                                'vehicle_type',
                                'two_wheeler'
                            )
                            ->count(),

                    'four_wheeler' =>
                        $vehicles
                            ->where(
                                'vehicle_type',
                                'four_wheeler'
                            )
                            ->count(),

                    'bicycle' =>
                        $vehicles
                            ->where(
                                'vehicle_type',
                                'bicycle'
                            )
                            ->count(),

                    'other' =>
                        $vehicles
                            ->where(
                                'vehicle_type',
                                'other'
                            )
                            ->count(),
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

        $validated = $request->validate([
            'vehicle_type' => [
                'required',
                Rule::in([
                    'two_wheeler',
                    'four_wheeler',
                    'bicycle',
                    'other',
                ]),
            ],

            'vehicle_number' => [
                'required',
                'string',
                'max:30',
            ],

            'color' => [
                'nullable',
                'string',
                'max:50',
            ],

            'model' => [
                'nullable',
                'string',
                'max:100',
            ],

            'rc_file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:8192',
            ],
        ]);

        $vehicleNumber = strtoupper(
            preg_replace(
                '/\s+/',
                '',
                trim(
                    $validated['vehicle_number']
                )
            )
        );

        $duplicateExists = Vehicle::query()
            ->where(
                'resident_id',
                $resident->id
            )
            ->whereRaw(
                'UPPER(REPLACE(vehicle_number, " ", "")) = ?',
                [$vehicleNumber]
            )
            ->exists();

        if ($duplicateExists) {
            return back()->withErrors([
                'vehicle_number' =>
                    'This vehicle number is already registered in your account.',
            ]);
        }

        $rcPath = null;

        if ($request->hasFile('rc_file')) {
            $rcPath = $request
                ->file('rc_file')
                ->store(
                    "vehicle-rc/{$resident->id}",
                    'public'
                );
        }

        Vehicle::create([
            'resident_id' =>
                $resident->id,

            'vehicle_type' =>
                $validated['vehicle_type'],

            'vehicle_number' =>
                $vehicleNumber,

            'color' =>
                $this->nullableTrim(
                    $validated['color'] ?? null
                ),

            'model' =>
                $this->nullableTrim(
                    $validated['model'] ?? null
                ),

            'rc_file_url' =>
                $rcPath,
        ]);

        return back()->with(
            'success',
            'Vehicle added successfully.'
        );
    }

    public function update(
        Request $request,
        Vehicle $vehicle
    ): RedirectResponse {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $this->authorizeVehicleOwnership(
            $vehicle,
            $resident
        );

        $validated = $request->validate([
            'vehicle_type' => [
                'required',
                Rule::in([
                    'two_wheeler',
                    'four_wheeler',
                    'bicycle',
                    'other',
                ]),
            ],

            'vehicle_number' => [
                'required',
                'string',
                'max:30',
            ],

            'color' => [
                'nullable',
                'string',
                'max:50',
            ],

            'model' => [
                'nullable',
                'string',
                'max:100',
            ],

            'rc_file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:8192',
            ],

            'remove_rc_file' => [
                'nullable',
                'boolean',
            ],
        ]);

        $vehicleNumber = strtoupper(
            preg_replace(
                '/\s+/',
                '',
                trim(
                    $validated['vehicle_number']
                )
            )
        );

        $duplicateExists = Vehicle::query()
            ->where(
                'resident_id',
                $resident->id
            )
            ->whereKeyNot($vehicle->id)
            ->whereRaw(
                'UPPER(REPLACE(vehicle_number, " ", "")) = ?',
                [$vehicleNumber]
            )
            ->exists();

        if ($duplicateExists) {
            return back()->withErrors([
                'vehicle_number' =>
                    'This vehicle number is already registered in your account.',
            ]);
        }

        DB::transaction(function () use ($request, $validated, $resident, $vehicle, $vehicleNumber): void {
            $oldRcPath =
                $vehicle->rcStoragePath();

            $newRcPath =
                $vehicle->rc_file_url;

            if (
                (bool) (
                    $validated['remove_rc_file']
                    ?? false
                )
            ) {
                if ($oldRcPath) {
                    Storage::disk('public')
                        ->delete($oldRcPath);
                }

                $newRcPath = null;
            }

            if ($request->hasFile('rc_file')) {
                $uploadedPath = $request
                    ->file('rc_file')
                    ->store(
                        "vehicle-rc/{$resident->id}",
                        'public'
                    );

                if ($oldRcPath) {
                    Storage::disk('public')
                        ->delete($oldRcPath);
                }

                $newRcPath = $uploadedPath;
            }

            $vehicle->update([
                'vehicle_type' =>
                    $validated['vehicle_type'],

                'vehicle_number' =>
                    $vehicleNumber,

                'color' =>
                    $this->nullableTrim(
                        $validated['color'] ?? null
                    ),

                'model' =>
                    $this->nullableTrim(
                        $validated['model'] ?? null
                    ),

                'rc_file_url' =>
                    $newRcPath,
            ]);
        });

        return back()->with(
            'success',
            'Vehicle updated successfully.'
        );
    }

    public function downloadRc(
        Vehicle $vehicle
    ): BinaryFileResponse {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $this->authorizeVehicleOwnership(
            $vehicle,
            $resident
        );

        $path = $vehicle->rcStoragePath();

        abort_unless(
            $path
            && Storage::disk('public')
                ->exists($path),
            404
        );

        return response()->download(
            Storage::disk('public')->path(
                $path
            ),
            basename($path)
        );
    }

    public function destroy(
        Vehicle $vehicle
    ): RedirectResponse {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $this->authorizeVehicleOwnership(
            $vehicle,
            $resident
        );

        $path = $vehicle->rcStoragePath();

        if ($path) {
            Storage::disk('public')->delete(
                $path
            );
        }

        $vehicle->delete();

        return back()->with(
            'success',
            'Vehicle removed successfully.'
        );
    }

    private function authorizeVehicleOwnership(
        Vehicle $vehicle,
        Resident $resident
    ): void {
        abort_unless(
            (int) $vehicle->resident_id
            === (int) $resident->id,
            403
        );
    }

    private function nullableTrim(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
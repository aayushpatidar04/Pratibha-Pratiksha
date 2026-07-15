<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Inventory;
use App\Models\Resident;
use App\Models\ResidentStay;
use App\Models\Room;
use App\Services\RoomAllotmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class CheckInOutController extends Controller
{
    public function index(Request $request): Response
    {
        $awaitingCheckIn = ResidentStay::with([
            'resident',
            'building',
            'floor',
            'room',
            'bed',
        ])
            ->where('status', 'upcoming')
            ->where('check_in_status', false)
            ->orderBy('check_in_date')
            ->get();

        $checkedInStays = ResidentStay::with([
            'resident',
            'building',
            'floor',
            'room',
            'bed',
            'inventoryAssignments' => function ($query) {
                $query
                    ->where('is_returned', false)
                    ->with('inventory');
            },
        ])
            ->where('status', 'active')
            ->where('check_in_status', true)
            ->orderByDesc('check_in_date')
            ->get();

        $unassigned = Resident::query()
            ->whereDoesntHave('stays', function ($query) {
                $query->whereIn('status', [
                    'upcoming',
                    'active',
                ]);
            })
            ->whereIn('status', [
                'upcoming',
                'active',
            ])
            ->orderBy('first_name')
            ->get([
                'id',
                'first_name',
                'last_name',
                'resident_code',
                'phone',
                'photo_url',
                'status',
            ]);

        $studentInventory = Inventory::query()
            ->where('category', 'student')
            ->where('available', '>', 0)
            ->orderBy('item_name')
            ->get([
                'id',
                'item_name',
                'available',
                'unit',
            ]);

        return Inertia::render('CheckInOut/Index', [
            'awaitingCheckIn' => $awaitingCheckIn,
            'checkedInStays' => $checkedInStays,
            'unassignedResidents' => $unassigned,

            'studentInventory' => $studentInventory,

            'buildings' => Building::orderBy('name')
                ->get(['id', 'name']),

            'floors' => Floor::orderBy('floor_number')
                ->get([
                    'id',
                    'name',
                    'building_id',
                ]),

            'rooms' => Room::with('beds')
                ->orderBy('room_number')
                ->get([
                    'id',
                    'room_number',
                    'building_id',
                    'floor_id',
                    'capacity',
                    'occupied_beds',
                    'monthly_rent_per_bed',
                ]),
        ]);
    }

    public function allotRoom(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => ['required', 'exists:residents,id'],
            'building_id' => ['required', 'exists:buildings,id'],
            'floor_id' => ['required', 'exists:floors,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'bed_id' => ['required', 'exists:beds,id'],
            'check_in_date' => ['required', 'date'],
            'expected_check_out_date' => [
                'nullable',
                'date',
                'after_or_equal:check_in_date',
            ],
            'rent_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'bill_type' => ['nullable', 'in:monthly,session,daily'],
            'notes' => ['nullable', 'string'],
            'billing_basis' => ['required', 'in:monthly,daily'],
            'daily_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'required_if:billing_basis,daily',
            ],
        ]);

        $resident = Resident::findOrFail(
            $validated['resident_id']
        );

        try {
            RoomAllotmentService::allot(
                $resident,
                $validated
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            "{$resident->first_name}'s room was allotted. Actual check-in is still pending."
        );
    }

    public function confirmCheckin(
        Request $request,
        ResidentStay $stay
    ): RedirectResponse {
        $validated = $request->validate([
            'check_in_date' => ['required', 'date'],

            'inventory' => ['nullable', 'array'],

            'inventory.*.inventory_id' => [
                'required',
                'exists:inventory,id',
            ],

            'inventory.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'inventory.*.condition_at_issue' => [
                'nullable',
                'in:new,good,fair,damaged',
            ],

            'inventory.*.notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        try {
            RoomAllotmentService::confirmCheckIn(
                $stay,
                $validated
            );
        } catch (
            \RuntimeException |
            ValidationException $e
        ) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            "{$stay->resident->first_name} checked in successfully."
        );
    }

    public function reviewCheckout(
        Request $request,
        ResidentStay $stay
    ): RedirectResponse {
        $validated = $request->validate([
            'actual_check_out_date' => [
                'required',
                'date',
                'after_or_equal:' .
                $stay->check_in_date->toDateString(),
            ],

            'decision' => [
                'required',
                Rule::in([
                    'approved',
                    'hold',
                    'rejected',
                ]),
            ],

            'checkout_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'inventory_returns' => [
                'nullable',
                'array',
            ],

            'inventory_returns.*.assignment_id' => [
                'required',
                'exists:resident_inventory_assignments,id',
            ],

            'inventory_returns.*.returned_good_quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'inventory_returns.*.returned_damaged_quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'inventory_returns.*.missing_quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'inventory_returns.*.return_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        try {
            RoomAllotmentService::reviewCheckout(
                $stay,
                $validated,
                $request->user()?->id
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\RuntimeException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }

        $message = match ($validated['decision']) {
            'approved' => 'Checkout approved successfully.',
            'hold' => 'Checkout has been placed on hold.',
            'rejected' => 'Checkout request rejected.',
        };

        return back()->with('success', $message);
    }
}
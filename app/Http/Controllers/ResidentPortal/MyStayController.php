<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyStayController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Resident $resident */
        $resident = $request->user('resident');

        $resident->load([
            'currentStay.building:id,name',
            'currentStay.floor:id,name,floor_number',
            'currentStay.room:id,room_number,capacity,monthly_rent_per_bed',
            'currentStay.bed:id,bed_number,status',
            'currentStay.checkedInByUser:id,name',
            'currentStay.checkoutReviewedBy:id,name',
        ]);

        $stay = $resident->currentStay;

        return Inertia::render(
            'ResidentPortal/MyStay/Index',
            [
                'stay' => $stay
                    ? [
                        'id' => $stay->id,
                        'building' => $stay->building,
                        'floor' => $stay->floor,
                        'room' => $stay->room,
                        'bed' => $stay->bed,

                        'check_in_date' =>
                            $stay->check_in_date,

                        'expected_check_out_date' =>
                            $stay->expected_check_out_date,

                        'actual_check_out_date' =>
                            $stay->actual_check_out_date,

                        'billing_basis' =>
                            $stay->billing_basis,

                        'bill_type' =>
                            $stay->bill_type,

                        'rent_amount' =>
                            (float) $stay->rent_amount,

                        'daily_rate' =>
                            (float) ($stay->daily_rate ?? 0),

                        'deposit_amount' =>
                            (float) $stay->deposit_amount,

                        'status' =>
                            $stay->status,

                        'notes' =>
                            $stay->notes,

                        'check_in_status' =>
                            (bool) $stay->check_in_status,

                        'checked_in_at' =>
                            $stay->checked_in_at,

                        'checked_in_by' =>
                            $stay->checkedInByUser?->name,

                        'checkout_status' =>
                            $stay->checkout_status,

                        'checkout_notes' =>
                            $stay->checkout_notes,

                        'checkout_reviewed_by' =>
                            $stay->checkoutReviewedBy?->name,

                        'checkout_reviewed_at' =>
                            $stay->checkout_reviewed_at,
                    ]
                    : null,
            ]
        );
    }
}
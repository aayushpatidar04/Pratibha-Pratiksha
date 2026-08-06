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
            'currentStay.inventoryAssignments' => function ($query) {
                $query
                    ->with([
                        'inventory:id,item_name,category,unit',
                    ])
                    ->orderByDesc('assigned_at')
                    ->orderByDesc('id');
            },
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
                
                'assignedAssets' => $stay
                    ? $stay->inventoryAssignments
                        ->map(function ($assignment) {
                            $processedQuantity =
                                (int) $assignment->returned_good_quantity
                                + (int) $assignment->returned_damaged_quantity
                                + (int) $assignment->missing_quantity;

                            $outstandingQuantity = max(
                                0,
                                (int) $assignment->quantity
                                - $processedQuantity
                            );

                            return [
                                'id' => $assignment->id,

                                'inventory_id' =>
                                    $assignment->inventory_id,

                                'item_name' =>
                                    $assignment->inventory?->item_name
                                    ?? 'Inventory Item',

                                'category' =>
                                    $assignment->inventory?->category,

                                'unit' =>
                                    $assignment->inventory?->unit
                                    ?? 'pieces',

                                'quantity' =>
                                    (int) $assignment->quantity,

                                'condition_at_issue' =>
                                    $assignment->condition_at_issue,

                                'issue_notes' =>
                                    $assignment->issue_notes,

                                'assigned_at' =>
                                    $assignment->assigned_at,

                                'is_returned' =>
                                    (bool) $assignment->is_returned,

                                'returned_quantity' =>
                                    (int) $assignment->returned_quantity,

                                'returned_good_quantity' =>
                                    (int) $assignment
                                        ->returned_good_quantity,

                                'returned_damaged_quantity' =>
                                    (int) $assignment
                                        ->returned_damaged_quantity,

                                'missing_quantity' =>
                                    (int) $assignment->missing_quantity,

                                'outstanding_quantity' =>
                                    $outstandingQuantity,

                                'condition_at_return' =>
                                    $assignment->condition_at_return,

                                'return_notes' =>
                                    $assignment->return_notes,

                                'returned_at' =>
                                    $assignment->returned_at,

                                'return_review_status' =>
                                    $assignment->return_review_status,
                            ];
                        })
                        ->values()
                    : [],

                'assetSummary' => $stay
                    ? [
                        'total_types' =>
                            $stay
                                ->inventoryAssignments
                                ->count(),

                        'total_quantity' =>
                            $stay
                                ->inventoryAssignments
                                ->sum('quantity'),

                        'active_quantity' =>
                            $stay
                                ->inventoryAssignments
                                ->sum(function ($assignment) {
                                    return max(
                                        0,
                                        (int) $assignment->quantity
                                        - (int) $assignment
                                            ->returned_good_quantity
                                        - (int) $assignment
                                            ->returned_damaged_quantity
                                        - (int) $assignment
                                            ->missing_quantity
                                    );
                                }),

                        'damaged_quantity' =>
                            $stay
                                ->inventoryAssignments
                                ->sum(
                                    'returned_damaged_quantity'
                                ),

                        'missing_quantity' =>
                            $stay
                                ->inventoryAssignments
                                ->sum('missing_quantity'),
                    ]
                    : [
                        'total_types' => 0,
                        'total_quantity' => 0,
                        'active_quantity' => 0,
                        'damaged_quantity' => 0,
                        'missing_quantity' => 0,
                    ],
            ]
        );
    }
}
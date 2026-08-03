<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\FeeInvoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Resident $resident */
        $resident = $request->user('resident');

        $resident->load([
            'currentStay.building:id,name',
            'currentStay.floor:id,name,floor_number',
            'currentStay.room:id,room_number',
            'currentStay.bed:id,bed_number',
        ]);

        $currentStay = $resident->currentStay;

        $invoiceQuery = FeeInvoice::query()
            ->where('resident_id', $resident->id);

        $outstandingAmount = (clone $invoiceQuery)
            ->whereIn('status', [
                'unpaid',
                'partial',
                'overdue',
            ])
            ->get()
            ->sum(function (FeeInvoice $invoice) {
                return max(
                    0,
                    (float) $invoice->amount -
                    (float) $invoice->paid_amount
                );
            });

        $nextDueInvoice = (clone $invoiceQuery)
            ->whereIn('status', [
                'unpaid',
                'partial',
                'overdue',
            ])
            ->orderBy('due_date')
            ->first([
                'id',
                'invoice_number',
                'amount',
                'paid_amount',
                'due_date',
                'status',
            ]);

        $recentInvoices = (clone $invoiceQuery)
            ->latest('created_at')
            ->limit(5)
            ->get([
                'id',
                'invoice_number',
                'fee_type',
                'description',
                'amount',
                'paid_amount',
                'status',
                'due_date',
                'created_at',
            ]);

        $recentPayments = Payment::query()
            ->where('resident_id', $resident->id)
            ->latest('payment_date')
            ->limit(5)
            ->get([
                'id',
                'invoice_id',
                'amount',
                'payment_mode',
                'transaction_id',
                'payment_date',
                'receipt_number',
            ]);

        return Inertia::render(
            'ResidentPortal/Dashboard',
            [
                'resident' => [
                    'id' => $resident->id,
                    'resident_code' => $resident->resident_code,
                    'first_name' => $resident->first_name,
                    'last_name' => $resident->last_name,
                    'name' => trim(
                        $resident->first_name . ' ' .
                        $resident->last_name
                    ),
                    'photo_url' => $resident->photo_url,
                    'status' => $resident->status,
                    'course' => $resident->course,
                    'institute' => $resident->institute,
                ],

                'currentStay' => $currentStay
                    ? [
                        'id' => $currentStay->id,
                        'building' =>
                            $currentStay->building?->name,
                        'floor' =>
                            $currentStay->floor?->name
                            ?? $currentStay->floor?->floor_number,
                        'room' =>
                            $currentStay->room?->room_number,
                        'bed' =>
                            $currentStay->bed?->bed_number,
                        'check_in_date' =>
                            $currentStay->check_in_date,
                        'expected_check_out_date' =>
                            $currentStay->expected_check_out_date,
                        'actual_check_out_date' =>
                            $currentStay->actual_check_out_date,
                        'billing_basis' =>
                            $currentStay->billing_basis,
                        'rent_amount' =>
                            (float) $currentStay->rent_amount,
                        'daily_rate' =>
                            (float) ($currentStay->daily_rate ?? 0),
                        'deposit_amount' =>
                            (float) $currentStay->deposit_amount,
                        'status' => $currentStay->status,
                    ]
                    : null,

                'billingSummary' => [
                    'outstanding_amount' =>
                        $outstandingAmount,

                    'next_due_date' =>
                        $nextDueInvoice?->due_date,

                    'next_due_invoice' =>
                        $nextDueInvoice?->invoice_number,

                    'next_due_amount' =>
                        $nextDueInvoice
                        ? max(
                            0,
                            (float) $nextDueInvoice->amount -
                            (float) $nextDueInvoice->paid_amount
                        )
                        : 0,

                    'total_invoices' =>
                        (clone $invoiceQuery)->count(),

                    'paid_invoices' =>
                        (clone $invoiceQuery)
                            ->where('status', 'paid')
                            ->count(),
                ],

                'recentInvoices' => $recentInvoices,

                'recentPayments' => $recentPayments,

                'summaryCounts' => [
                    'pending_leaves' => LeaveRequest::query()
                        ->where(
                            'resident_id',
                            $resident->id
                        )
                        ->whereIn(
                            'final_status',
                            [
                                'pending',
                                'parent_approval_pending',
                            ]
                        )
                        ->count(),
                    'open_complaints' => 0,
                    'pending_requests' => 0,
                    'unread_notices' => 0,
                ],
            ]
        );
    }
}
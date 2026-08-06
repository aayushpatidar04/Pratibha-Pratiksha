<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\FeeInvoice;
use App\Models\Payment;
use App\Models\Resident;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'in:all,paid,pending,partial,overdue',
            ],

            'fee_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'due_from' => [
                'nullable',
                'date',
            ],

            'due_to' => [
                'nullable',
                'date',
                'after_or_equal:due_from',
            ],

            'sort' => [
                'nullable',
                'in:newest,oldest,due_soon,amount_high,amount_low',
            ],
        ]);

        $filters = [
            'search' => trim($validated['search'] ?? ''),
            'status' => $validated['status'] ?? 'all',
            'fee_type' => $validated['fee_type'] ?? '',
            'due_from' => $validated['due_from'] ?? '',
            'due_to' => $validated['due_to'] ?? '',
            'sort' => $validated['sort'] ?? 'newest',
        ];

        $baseQuery = FeeInvoice::query()
            ->where('resident_id', $resident->id);

        $invoiceQuery = (clone $baseQuery)
            ->with([
                'items:id,invoice_id,item_type,title,amount,is_late_fee',
            ])
            ->withSum('payments as total_received', 'amount')
            ->when(
                $filters['search'] !== '',
                function (Builder $query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function (Builder $query) use ($search) {
                        $query
                            ->where(
                                'invoice_number',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'description',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'fee_type',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )
            ->when(
                $filters['status'] !== 'all',
                fn(Builder $query) =>
                $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                $filters['fee_type'] !== '',
                fn(Builder $query) =>
                $query->where(
                    'fee_type',
                    $filters['fee_type']
                )
            )
            ->when(
                $filters['due_from'] !== '',
                fn(Builder $query) =>
                $query->whereDate(
                    'due_date',
                    '>=',
                    $filters['due_from']
                )
            )
            ->when(
                $filters['due_to'] !== '',
                fn(Builder $query) =>
                $query->whereDate(
                    'due_date',
                    '<=',
                    $filters['due_to']
                )
            );

        $this->applySorting(
            $invoiceQuery,
            $filters['sort']
        );

        $invoices = $invoiceQuery
            ->paginate(15)
            ->withQueryString()
            ->through(function (FeeInvoice $invoice) {
                $amount = (float) $invoice->amount;
                $paidAmount = (float) $invoice->paid_amount;

                $calculatedReceived = (float) (
                    $invoice->total_received ?? 0
                );

                /*
                 * paid_amount is the invoice's stored accounting value.
                 * total_received is included as a cross-check from payments.
                 */
                $effectivePaid = max(
                    $paidAmount,
                    $calculatedReceived
                );

                return [
                    'id' => $invoice->id,

                    'invoice_number' =>
                        $invoice->invoice_number,

                    'fee_type' =>
                        $invoice->fee_type,

                    'description' =>
                        $invoice->description,

                    'amount' =>
                        $amount,

                    'paid_amount' =>
                        $effectivePaid,

                    'balance_amount' =>
                        max(0, $amount - $effectivePaid),

                    'status' =>
                        $invoice->status,

                    'due_date' =>
                        $invoice->due_date,

                    'created_at' =>
                        $invoice->created_at,

                    'items_count' =>
                        $invoice->items->count(),

                    'late_fee_amount' =>
                        (float) $invoice->items
                            ->where('is_late_fee', true)
                            ->sum('amount'),

                    'can_download' => true,
                ];
            });

        $summary = $this->buildSummary(
            $baseQuery,
            $resident
        );

        $feeTypes = (clone $baseQuery)
            ->whereNotNull('fee_type')
            ->where('fee_type', '!=', '')
            ->distinct()
            ->orderBy('fee_type')
            ->pluck('fee_type')
            ->values();

        $recentPayments = Payment::query()
            ->where('resident_id', $resident->id)
            ->with([
                'invoice:id,invoice_number,description',
            ])
            ->latest('payment_date')
            ->limit(5)
            ->get()
            ->map(function (Payment $payment) {
                return [
                    'id' => $payment->id,

                    'invoice_id' =>
                        $payment->invoice_id,

                    'invoice_number' =>
                        $payment->invoice?->invoice_number,

                    'invoice_description' =>
                        $payment->invoice?->description,

                    'amount' =>
                        (float) $payment->amount,

                    'payment_mode' =>
                        $payment->payment_mode,

                    'payment_date' =>
                        $payment->payment_date,

                    'receipt_number' =>
                        $payment->receipt_number,

                    'transaction_id' =>
                        $payment->transaction_id,
                ];
            });

        return Inertia::render(
            'ResidentPortal/Billing/Index',
            [
                'filters' => $filters,

                'invoices' => $invoices,

                'summary' => $summary,

                'feeTypes' => $feeTypes,

                'recentPayments' => $recentPayments,
            ]
        );
    }

    public function show(FeeInvoice $invoice): Response
    {
        $resident = Auth::guard('resident')->user();

        abort_unless(
            (int) $invoice->resident_id === (int) $resident->id,
            403
        );
        $invoice->load([
            'resident',
            'stay.building',
            'stay.room',
            'stay.bed',
            'items',
            'payments.proofs',
        ]);

        $payments = $invoice->payments
            ->sortByDesc('payment_date')
            ->values()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,

                    'amount' => (float) $payment->amount,

                    'payment_mode' => $payment->payment_mode,

                    'payment_date' => $payment->payment_date,

                    'transaction_id' => $payment->transaction_id,

                    'receipt_number' => $payment->receipt_number,

                    'notes' => $payment->notes,

                    'proofs' => $payment->proofs->map(function ($proof) {
                        return [
                            'id' => $proof->id,
                            'file_path' => $proof->file_path,
                            'original_name' => $proof->original_name,
                        ];
                    }),
                ];
            });

        return Inertia::render(
            'ResidentPortal/Billing/Show',
            [

                'invoice' => [

                    'id' => $invoice->id,

                    'invoice_number' => $invoice->invoice_number,

                    'fee_type' => $invoice->fee_type,

                    'status' => $invoice->status,

                    'description' => $invoice->description,

                    'amount' => (float) $invoice->amount,

                    'paid_amount' => (float) $invoice->paid_amount,

                    'balance_amount' => max(
                        0,
                        (float) $invoice->amount -
                        (float) $invoice->paid_amount
                    ),

                    'due_date' => $invoice->due_date,

                    'created_at' => $invoice->created_at,

                    'late_fee' => (float) $invoice->items
                        ->where('is_late_fee', true)
                        ->sum('amount'),

                    'items' => $invoice->items->map(function ($item) {

                        return [

                            'title' => $item->title,

                            'item_type' => $item->item_type,

                            'amount' => (float) $item->amount,

                            'description' => $item->description,

                            'is_late_fee' => $item->is_late_fee,

                        ];

                    }),

                    'stay' => $invoice->stay
                        ? [

                            'building' => $invoice->stay->building?->name,

                            'room' => $invoice->stay->room?->room_number,

                            'bed' => $invoice->stay->bed?->bed_number,

                            'check_in_date' => $invoice->stay->check_in_date,

                            'check_out_date' => $invoice->stay->actual_check_out_date,

                        ]
                        : null,

                ],

                'payments' => $payments,

            ]
        );
    }

    // ==================== PDF EXPORTS ====================
    public function exportPdfEnglish(FeeInvoice $invoice)
    {
        $invoice->load([
            'resident',
            'application',
            'stay.room',
            'stay.bed',
            'items',
            'payments.proofs',
            'monthlyConfig',
            'waivedByUser',
        ]);

        $invoice->status = $invoice->computed_status;
        $invoice->late_fee_amount = $invoice->effective_late_fee_amount;

        $pdf = Pdf::loadView('pdf.invoices.english', [
            'invoice' => $invoice,
        ])->setPaper('A6', 'portrait');

        return $pdf->stream(
            $invoice->invoice_number . '-english.pdf'
        );
    }

    public function previewEnglish(FeeInvoice $invoice)
    {
        $invoice->load([
            'resident',
            'application',
            'stay.room',
            'stay.bed',
            'items',
            'payments.proofs',
            'monthlyConfig',
            'waivedByUser',
        ]);
 
        $invoice->status = $invoice->computed_status;
        $invoice->late_fee_amount = $invoice->effective_late_fee_amount;
 
        return view('pdf.invoices.english-preview', [
            'invoice' => $invoice,
        ]);
    }

    public function exportPdfHindi(FeeInvoice $invoice)
    {
        $invoice->load(['resident', 'items', 'payments.proofs', 'monthlyConfig', 'waivedByUser']);
        $invoice->status = $invoice->computed_status;
        $invoice->late_fee_amount = $invoice->effective_late_fee_amount;

        $pdf = Pdf::loadView('pdf.invoices.hindi', [
            'invoice' => $invoice,
        ])->setPaper('A5', 'portrait');

        return $pdf->stream($invoice->invoice_number . '-hindi.pdf');
    }

    public function previewHindi(FeeInvoice $invoice)
    {
        $invoice->load([
            'resident',
            'application',
            'stay.room',
            'stay.bed',
            'items',
            'payments.proofs',
            'monthlyConfig',
            'waivedByUser',
        ]);

        $invoice->status = $invoice->computed_status;
        $invoice->late_fee_amount = $invoice->effective_late_fee_amount;

        return view('pdf.invoices.hindi-preview', [
            'invoice' => $invoice,
        ]);
    }

    protected function buildSummary(
        Builder $baseQuery,
        Resident $resident
    ): array {
        $invoices = (clone $baseQuery)
            ->get([
                'id',
                'amount',
                'paid_amount',
                'status',
                'due_date',
            ]);

        $totalBilled = (float) $invoices->sum(
            fn(FeeInvoice $invoice) =>
            (float) $invoice->amount
        );

        $totalPaid = (float) Payment::query()
            ->where('resident_id', $resident->id)
            ->sum('amount');

        $outstanding = (float) $invoices->sum(
            function (FeeInvoice $invoice) {
                return max(
                    0,
                    (float) $invoice->amount -
                    (float) $invoice->paid_amount
                );
            }
        );

        $nextDueInvoice = (clone $baseQuery)
            ->whereIn('status', [
                'pending',
                'partial',
                'overdue',
            ])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->first([
                'id',
                'invoice_number',
                'amount',
                'paid_amount',
                'due_date',
                'status',
            ]);

        return [
            'total_billed' =>
                $totalBilled,

            'total_paid' =>
                $totalPaid,

            'outstanding_amount' =>
                $outstanding,

            'paid_count' =>
                $invoices->where('status', 'paid')->count(),

            'pending_count' =>
                $invoices->where('status', 'pending')->count(),

            'partial_count' =>
                $invoices->where('status', 'partial')->count(),

            'overdue_count' =>
                $invoices->where('status', 'overdue')->count(),

            'total_invoices' =>
                $invoices->count(),

            'next_due_invoice' =>
                $nextDueInvoice
                ? [
                    'id' =>
                        $nextDueInvoice->id,

                    'invoice_number' =>
                        $nextDueInvoice->invoice_number,

                    'due_date' =>
                        $nextDueInvoice->due_date,

                    'status' =>
                        $nextDueInvoice->status,

                    'balance_amount' =>
                        max(
                            0,
                            (float) $nextDueInvoice->amount -
                            (float) $nextDueInvoice->paid_amount
                        ),
                ]
                : null,
        ];
    }

    protected function applySorting(
        Builder $query,
        string $sort
    ): void {
        match ($sort) {
            'oldest' =>
            $query->oldest('created_at'),

            'due_soon' =>
            $query
                ->orderByRaw(
                    'CASE WHEN due_date IS NULL THEN 1 ELSE 0 END'
                )
                ->orderBy('due_date')
                ->latest('created_at'),

            'amount_high' =>
            $query
                ->orderByDesc('amount')
                ->latest('created_at'),

            'amount_low' =>
            $query
                ->orderBy('amount')
                ->latest('created_at'),

            default =>
            $query->latest('created_at'),
        };
    }

    public function paymentReceipt(Payment $payment)
    {
        $payment->load([
            'invoice.resident',
            'invoice.application',
            'invoice.stay.room',
            'invoice.stay.bed',
            'invoice.items',
            'proofs',
        ]);
        $payment->invoice->late_fee_amount = $payment->invoice->effective_late_fee_amount;

        return view('billing.payment-receipt', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
        ]);
    }
}
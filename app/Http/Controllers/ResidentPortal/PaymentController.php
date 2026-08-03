<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
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

            'payment_mode' => [
                'nullable',
                'string',
                'max:50',
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],

            'sort' => [
                'nullable',
                'in:newest,oldest,amount_high,amount_low',
            ],
        ]);

        $filters = [
            'search' => trim($validated['search'] ?? ''),
            'payment_mode' => $validated['payment_mode'] ?? '',
            'date_from' => $validated['date_from'] ?? '',
            'date_to' => $validated['date_to'] ?? '',
            'sort' => $validated['sort'] ?? 'newest',
        ];

        $baseQuery = Payment::query()
            ->where('resident_id', $resident->id);

        $paymentQuery = (clone $baseQuery)
            ->with([
                'invoice:id,invoice_number,description,fee_type,status',
                'proofs:id,payment_id,file_path,file_type,original_name',
            ])
            ->when(
                $filters['search'] !== '',
                function (Builder $query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function (Builder $query) use ($search) {
                        $query
                            ->where(
                                'receipt_number',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'transaction_id',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhereHas(
                                'invoice',
                                function (Builder $invoiceQuery) use ($search) {
                                    $invoiceQuery
                                        ->where(
                                            'invoice_number',
                                            'like',
                                            "%{$search}%"
                                        )
                                        ->orWhere(
                                            'description',
                                            'like',
                                            "%{$search}%"
                                        );
                                }
                            );
                    });
                }
            )
            ->when(
                $filters['payment_mode'] !== '',
                fn (Builder $query) => $query->where(
                    'payment_mode',
                    $filters['payment_mode']
                )
            )
            ->when(
                $filters['date_from'] !== '',
                fn (Builder $query) => $query->whereDate(
                    'payment_date',
                    '>=',
                    $filters['date_from']
                )
            )
            ->when(
                $filters['date_to'] !== '',
                fn (Builder $query) => $query->whereDate(
                    'payment_date',
                    '<=',
                    $filters['date_to']
                )
            );

        $this->applySorting(
            $paymentQuery,
            $filters['sort']
        );

        $payments = $paymentQuery
            ->paginate(15)
            ->withQueryString()
            ->through(function (Payment $payment) {
                return [
                    'id' => $payment->id,

                    'invoice_id' => $payment->invoice_id,

                    'invoice' => $payment->invoice
                        ? [
                            'id' => $payment->invoice->id,

                            'invoice_number' =>
                                $payment->invoice->invoice_number,

                            'description' =>
                                $payment->invoice->description,

                            'fee_type' =>
                                $payment->invoice->fee_type,

                            'status' =>
                                $payment->invoice->status,
                        ]
                        : null,

                    'amount' => (float) $payment->amount,

                    'payment_mode' =>
                        $payment->payment_mode,

                    'transaction_id' =>
                        $payment->transaction_id,

                    'payment_date' =>
                        $payment->payment_date,

                    'notes' =>
                        $payment->notes,

                    'receipt_number' =>
                        $payment->receipt_number,

                    'proofs' => $payment->proofs
                        ->map(function ($proof) {
                            return [
                                'id' => $proof->id,

                                'file_path' =>
                                    $proof->file_path,

                                'file_type' =>
                                    $proof->file_type,

                                'original_name' =>
                                    $proof->original_name,
                            ];
                        })
                        ->values(),
                ];
            });

        $summary = [
            'total_received' => (float) (clone $baseQuery)
                ->sum('amount'),

            'total_payments' => (clone $baseQuery)
                ->count(),

            'cash_received' => (float) (clone $baseQuery)
                ->where('payment_mode', 'cash')
                ->sum('amount'),

            'online_received' => (float) (clone $baseQuery)
                ->whereIn('payment_mode', [
                    'card',
                    'razorpay',
                    'upi',
                    'bank_transfer',
                    'online',
                ])
                ->sum('amount'),

            'payments_this_month' => (clone $baseQuery)
                ->whereYear('payment_date', now()->year)
                ->whereMonth('payment_date', now()->month)
                ->count(),

            'amount_this_month' => (float) (clone $baseQuery)
                ->whereYear('payment_date', now()->year)
                ->whereMonth('payment_date', now()->month)
                ->sum('amount'),
        ];

        $paymentModes = (clone $baseQuery)
            ->whereNotNull('payment_mode')
            ->where('payment_mode', '!=', '')
            ->distinct()
            ->orderBy('payment_mode')
            ->pluck('payment_mode')
            ->values();

        return Inertia::render(
            'ResidentPortal/Payments/Index',
            [
                'filters' => $filters,
                'payments' => $payments,
                'summary' => $summary,
                'paymentModes' => $paymentModes,
            ]
        );
    }

    public function show(Payment $payment): Response
    {
        /** @var Resident|null $resident */
        $resident = Auth::guard('resident')->user();

        abort_unless($resident, 401);

        abort_unless(
            (int) $payment->resident_id ===
                (int) $resident->id,
            403
        );

        $payment->load([
            'invoice:id,resident_id,invoice_number,description,fee_type,amount,paid_amount,status,due_date',
            'proofs:id,payment_id,file_path,file_type,original_name,created_at',
        ]);

        return Inertia::render(
            'ResidentPortal/Payments/Show',
            [
                'payment' => [
                    'id' => $payment->id,

                    'amount' =>
                        (float) $payment->amount,

                    'payment_mode' =>
                        $payment->payment_mode,

                    'transaction_id' =>
                        $payment->transaction_id,

                    'payment_date' =>
                        $payment->payment_date,

                    'notes' =>
                        $payment->notes,

                    'receipt_number' =>
                        $payment->receipt_number,

                    'created_at' =>
                        $payment->created_at,

                    'invoice' => $payment->invoice
                        ? [
                            'id' =>
                                $payment->invoice->id,

                            'invoice_number' =>
                                $payment->invoice->invoice_number,

                            'description' =>
                                $payment->invoice->description,

                            'fee_type' =>
                                $payment->invoice->fee_type,

                            'amount' =>
                                (float) $payment->invoice->amount,

                            'paid_amount' =>
                                (float) $payment->invoice->paid_amount,

                            'status' =>
                                $payment->invoice->status,

                            'due_date' =>
                                $payment->invoice->due_date,
                        ]
                        : null,

                    'proofs' => $payment->proofs
                        ->map(function ($proof) {
                            return [
                                'id' => $proof->id,

                                'file_path' =>
                                    $proof->file_path,

                                'file_type' =>
                                    $proof->file_type,

                                'original_name' =>
                                    $proof->original_name,

                                'created_at' =>
                                    $proof->created_at,
                            ];
                        })
                        ->values(),
                ],
            ]
        );
    }

    protected function applySorting(
        Builder $query,
        string $sort
    ): void {
        match ($sort) {
            'oldest' =>
                $query
                    ->orderBy('payment_date')
                    ->oldest('id'),

            'amount_high' =>
                $query
                    ->orderByDesc('amount')
                    ->latest('payment_date'),

            'amount_low' =>
                $query
                    ->orderBy('amount')
                    ->latest('payment_date'),

            default =>
                $query
                    ->latest('payment_date')
                    ->latest('id'),
        };
    }
}
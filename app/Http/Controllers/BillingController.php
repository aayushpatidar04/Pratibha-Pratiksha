<?php
// app/Http/Controllers/BillingController.php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\MonthlyBillingConfig;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Resident;
use App\Models\ResidentStay;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class BillingController extends Controller
{
    // ==================== INDEX ====================
    public function index(Request $request): Response
    {
        $query = FeeInvoice::with(['resident', 'payments.proofs', 'items', 'monthlyConfig', 'waivedByUser']);

        $deletedFilter = $request->string('deleted')->toString();

        if ($deletedFilter === 'only') {
            $query->onlyTrashed();
        } elseif ($deletedFilter === 'with') {
            $query->withTrashed();
        }

        // Status filter with late_fee_pending
        $status = $request->string('status')->toString();
        if ($status && $status !== 'all') {
            if ($status === 'late_fee_pending') {
                $query->whereRaw('paid_amount >= amount')
                    ->where('late_fee_amount', '>', 0)
                    ->where('late_fee_waived', false);
            } else {
                $query->where('status', $status);
            }
        }

        if ($residentId = $request->integer('resident_id')) {
            $query->where('resident_id', $residentId);
        }

        if ($month = $request->integer('month')) {
            $query->whereHas('monthlyConfig', fn($q) => $q->where('month', $month));
        }

        if ($year = $request->integer('year')) {
            $query->whereHas('monthlyConfig', fn($q) => $q->where('year', $year));
        }

        $invoices = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Update computed status
        foreach ($invoices as $invoice) {
            $invoice->status = $invoice->computed_status;
            $invoice->late_fee_amount = $invoice->effective_late_fee_amount;
        }

        $stats = [
            'totalBilled' => (float) FeeInvoice::sum('amount'),
            'totalLateFees' => FeeInvoice::where('late_fee_waived', false)->get()->sum(fn ($invoice) => $invoice->effective_late_fee_amount),
            'paidAmount' => (float) FeeInvoice::sum('paid_amount'),
            'pendingCount' => FeeInvoice::where('status', 'pending')->count(),
            'partialCount' => FeeInvoice::where('status', 'partial')->count(),
            'lateFeePendingCount' => FeeInvoice::where('status', 'late_fee_pending')->count(),
            'overdueCount' => FeeInvoice::where('status', 'overdue')->count(),
            'paidCount' => FeeInvoice::where('status', 'paid')->count(),
            'waivedLateFees' => (float) FeeInvoice::where('late_fee_waived', true)->sum('late_fee_amount'),
            'deletedCount' => FeeInvoice::onlyTrashed()->count(),
        ];

        return Inertia::render('Billing/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => $request->only('status', 'resident_id', 'month', 'year', 'deleted'),
            'residents' => Resident::where('status', 'active')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'resident_code']),
            'monthlyConfigs' => MonthlyBillingConfig::orderByDesc('year')
                ->orderByDesc('month')
                ->get(),
        ]);
    }

    // ==================== RESIDENT PAYMENT HISTORY ====================
    public function residentHistory(Request $request, Resident $resident): Response
    {
        $invoices = FeeInvoice::with(['payments.proofs', 'items', 'monthlyConfig'])
            ->where('resident_id', $resident->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        foreach ($invoices as $invoice) {
            $invoice->status = $invoice->computed_status;
            $invoice->late_fee_amount = $invoice->effective_late_fee_amount;
        }

        $payments = Payment::with(['invoice', 'proofs'])
            ->where('resident_id', $resident->id)
            ->orderByDesc('payment_date')
            ->get();

        $summary = [
            'totalBilled' => (float) FeeInvoice::where('resident_id', $resident->id)->sum('amount'),
            'totalPaid' => (float) Payment::where('resident_id', $resident->id)->sum('amount'),
            'totalLateFees' => (float) FeeInvoice::where('resident_id', $resident->id)
                ->where('late_fee_waived', false)->sum('late_fee_amount'),
            'pendingInvoices' => FeeInvoice::where('resident_id', $resident->id)
                ->where('status', '!=', 'paid')->count(),
        ];

        return Inertia::render('Billing/ResidentHistory', [
            'resident' => $resident,
            'invoices' => $invoices,
            'payments' => $payments,
            'summary' => $summary,
        ]);
    }

    // ==================== MONTHLY CONFIG ====================
    public function configIndex(): Response
    {
        return Inertia::render('Billing/Config/Index', [
            'configs' => MonthlyBillingConfig::with('creator')
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->paginate(12),
        ]);
    }

    public function configCreate(): Response
    {
        $nextMonth = now()->addMonth();

        return Inertia::render('Billing/Config/Create', [
            'suggestedMonth' => $nextMonth->month,
            'suggestedYear' => $nextMonth->year,
            'suggestedGenerationDate' => $nextMonth->copy()->subMonth()->day(28)->format('Y-m-d'),
            'suggestedDueDate' => $nextMonth->copy()->day(5)->format('Y-m-d'),
        ]);
    }

    public function configStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'rent_enabled' => 'boolean',
            'mess_enabled' => 'boolean',
            'cooler_enabled' => 'boolean',
            'default_mess_amount' => ['nullable', 'numeric', 'min:0', 'required_if:mess_enabled,true',],
            'default_cooler_amount' => ['nullable', 'numeric', 'min:0', 'required_if:cooler_enabled,true',],
            'custom_charges' => 'nullable|array',
            'custom_charges.*.name' => 'required_with:custom_charges|string|max:100',
            'custom_charges.*.amount' => 'required_with:custom_charges|numeric|min:0',
            'generation_date' => 'required|date',
            'due_date' => 'required|date|after:generation_date',
            'late_fee_per_day' => ['nullable', 'numeric', 'min:0', 'required_if:late_fee_enabled,true'],
            'late_fee_enabled' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $exists = MonthlyBillingConfig::where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Configuration for this month already exists.');
        }

        MonthlyBillingConfig::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('billing.config.index')
            ->with('success', 'Monthly billing configuration saved.');
    }

    public function destroyConfig(MonthlyBillingConfig $config): RedirectResponse
    {
        $invoiceCount = FeeInvoice::where('monthly_config_id', $config->id)->count();

        if ($invoiceCount > 0) {
            return back()->with(
                'error',
                "This configuration cannot be deleted because {$invoiceCount} invoice(s) have already been generated from it."
            );
        }

        DB::transaction(function () use ($config) {
            $config->delete();
        });

        return back()->with('success', 'Billing configuration deleted successfully.');
    }

    // ==================== AUTO GENERATE BILLS ====================
    public function autoGenerate(
        Request $request,
        MonthlyBillingConfig $config
    ): Response {
        $billingMonthStart = Carbon::create(
            $config->year,
            $config->month,
            1
        )->startOfMonth();

        $billingMonthEnd = $billingMonthStart->copy()->endOfMonth();

        /*
         * Include a stay if:
         *
         * 1. It starts on or before the end of the billing month.
         * 2. It has no checkout date, or checkout is on/after the start
         *    of the billing month.
         *
         * This works even when generating next month's bill in advance.
         */
        $eligibleStays = ResidentStay::query()
            ->with([
                'resident.amenityOverride',
                'room',
            ])
            ->whereDate('check_in_date', '<=', $billingMonthEnd)
            ->where('status', 'active')
            ->where(function ($query) use ($billingMonthStart) {
                $query
                    ->whereNull('actual_check_out_date')
                    ->orWhereDate(
                        'actual_check_out_date',
                        '>=',
                        $billingMonthStart
                    );
            })
            ->whereHas('resident', function ($query) {
                $query->whereIn('status', [
                    'active',
                    'upcoming',
                ]);
            })
            ->get();

        $preview = $eligibleStays->map(function ($stay) use ($config) {
            $resident = $stay->resident;
            $override = $resident->amenityOverride;

            $items = [];
            $total = 0;

            /*
             * Existing bill for this config/resident.
             * Prevent generating the same monthly bill twice.
             */
            $existingInvoice = FeeInvoice::query()
                ->where('resident_id', $resident->id)
                ->where('stay_id', $stay->id)
                ->where('monthly_config_id', $config->id)
                ->first();

            if ($existingInvoice) {
                return [
                    'stay_id' => $stay->id,
                    'resident_id' => $resident->id,
                    'resident_name' => trim(
                        $resident->first_name . ' ' . $resident->last_name
                    ),
                    'resident_code' => $resident->resident_code,
                    'room' => $stay->room?->room_number,
                    'items' => [],
                    'total' => 0,
                    'has_override' => $override !== null,
                    'skip' => true,
                    'skip_reason' => 'Invoice already generated',
                    'existing_invoice_id' => $existingInvoice->id,
                ];
            }

            /*
             * Room rent
             *
             * Override:
             * - null means follow monthly config.
             * - false means explicitly disable it.
             * - true means explicitly enable it.
             */
            $rentEnabled = $override?->rent_enabled
                ?? (bool) $config->rent_enabled;

            if ($rentEnabled) {
                $rentAmount = $override?->custom_rent;

                if ($rentAmount === null) {
                    $rentAmount = $stay->rent_amount;
                }

                $rentAmount = (float) ($rentAmount ?? 0);

                if ($rentAmount > 0) {
                    $items[] = [
                        'type' => 'rent',
                        'label' => 'Room Rent',
                        'amount' => $rentAmount,
                        'is_custom' => $override?->custom_rent !== null,
                    ];

                    $total += $rentAmount;
                }
            }

            /*
             * Mess charges
             */
            $messEnabled = $override?->mess_enabled
                ?? (bool) $config->mess_enabled;

            if ($messEnabled) {
                $messAmount = $override?->custom_mess;

                if ($messAmount === null) {
                    $messAmount = $config->default_mess_amount;
                }

                $messAmount = (float) ($messAmount ?? 0);

                if ($messAmount > 0) {
                    $items[] = [
                        'type' => 'mess',
                        'label' => 'Mess Charges',
                        'amount' => $messAmount,
                        'is_custom' => $override?->custom_mess !== null,
                    ];

                    $total += $messAmount;
                }
            }
            /*
             * Cooler charges
             */
            $coolerEnabled = $override?->cooler_enabled
                ?? (bool) $config->cooler_enabled;

            if ($coolerEnabled) {
                $coolerAmount = $override?->custom_cooler;

                if ($coolerAmount === null) {
                    $coolerAmount = $config->default_cooler_amount;
                }

                $coolerAmount = (float) ($coolerAmount ?? 0);

                if ($coolerAmount > 0) {
                    $items[] = [
                        'type' => 'cooler',
                        'label' => 'Cooler Charges',
                        'amount' => $coolerAmount,
                        'is_custom' => $override?->custom_cooler !== null,
                    ];

                    $total += $coolerAmount;
                }
            }

            /*
             * Config-wide custom charges.
             */
            foreach ($config->custom_charges ?? [] as $custom) {
                $name = trim((string) ($custom['name'] ?? ''));
                $amount = (float) ($custom['amount'] ?? 0);

                if ($name === '' || $amount <= 0) {
                    continue;
                }

                $items[] = [
                    'type' => 'custom',
                    'label' => $name,
                    'amount' => $amount,
                    'is_custom' => false,
                ];

                $total += $amount;
            }

            return [
                'stay_id' => $stay->id,
                'resident_id' => $resident->id,
                'resident_name' => trim(
                    $resident->first_name . ' ' . $resident->last_name
                ),
                'resident_code' => $resident->resident_code,
                'room' => $stay->room?->room_number,
                'items' => $items,
                'total' => round($total, 2),
                'has_override' => $override !== null,
                'skip' => empty($items),
                'skip_reason' => empty($items)
                    ? 'No enabled charge has a valid amount'
                    : null,
                'existing_invoice_id' => null,
            ];
        })->values();

        return Inertia::render('Billing/GeneratePreview', [
            'config' => $config,
            'preview' => $preview,
        ]);
    }

    // Actually generate after preview confirmation
    public function confirmGenerate(
        Request $request,
        MonthlyBillingConfig $config
    ): RedirectResponse {
        $validated = $request->validate([
            'selected_residents' => ['required', 'array', 'min:1'],
            'selected_residents.*' => ['required', 'integer', 'exists:residents,id'],
        ]);

        $billingMonthStart = Carbon::create(
            $config->year,
            $config->month,
            1
        )->startOfMonth();

        $billingMonthEnd = $billingMonthStart->copy()->endOfMonth();

        $generated = 0;
        $skipped = 0;

        /*
         * Do not rely only on status=active/current checkout state,
         * because bills may be generated in advance for the configured month.
         *
         * A stay is eligible when it overlaps the configured billing month.
         */
        $eligibleStays = ResidentStay::query()
            ->with([
                'resident.amenityOverride',
                'room',
            ])
            ->whereIn('resident_id', $validated['selected_residents'])
            ->whereDate('check_in_date', '<=', $billingMonthEnd)
            ->where('status', 'active')
            ->where(function ($query) use ($billingMonthStart) {
                $query
                    ->whereNull('actual_check_out_date')
                    ->orWhereDate(
                        'actual_check_out_date',
                        '>=',
                        $billingMonthStart
                    );
            })
            ->where(function ($query) {
                $query
                    ->whereNull('billing_basis')
                    ->orWhere('billing_basis', 'monthly');
            })
            ->whereHas('resident', function ($query) {
                $query->whereIn('status', [
                    'active',
                    'upcoming',
                ]);
            })
            ->get();

        DB::transaction(function () use ($config, $eligibleStays, &$generated, &$skipped) {
            foreach ($eligibleStays as $stay) {
                $resident = $stay->resident;

                if (!$resident) {
                    $skipped++;
                    continue;
                }

                /*
                 * One invoice per resident/stay/config.
                 */
                $alreadyExists = FeeInvoice::query()
                    ->where('resident_id', $resident->id)
                    ->where('stay_id', $stay->id)
                    ->where('monthly_config_id', $config->id)
                    ->exists();

                if ($alreadyExists) {
                    $skipped++;
                    continue;
                }

                $override = $resident->amenityOverride;

                $items = [];
                $totalAmount = 0;

                /*
                 * Room rent
                 */
                $rentEnabled = $override?->rent_enabled
                    ?? (bool) $config->rent_enabled;

                if ($rentEnabled) {
                    $rentAmount = $override?->custom_rent;

                    if ($rentAmount === null) {
                        $rentAmount = $stay->rent_amount;
                    }

                    $rentAmount = (float) ($rentAmount ?? 0);

                    if ($rentAmount > 0) {
                        $items[] = [
                            'item_type' => 'rent',
                            'amenity_type' => null,
                            'title' => 'Room Rent',
                            'amount' => $rentAmount,
                            'description' => null,
                            'is_late_fee' => false,
                        ];

                        $totalAmount += $rentAmount;
                    }
                }

                /*
                 * Mess charges
                 */
                $messEnabled = $override?->mess_enabled
                    ?? (bool) $config->mess_enabled;

                if ($messEnabled) {
                    $messAmount = $override?->custom_mess;

                    if ($messAmount === null) {
                        $messAmount = $config->default_mess_amount;
                    }

                    $messAmount = (float) ($messAmount ?? 0);

                    if ($messAmount > 0) {
                        $items[] = [
                            'item_type' => 'mess',
                            'amenity_type' => null,
                            'title' => 'Mess Charges',
                            'amount' => $messAmount,
                            'description' => null,
                            'is_late_fee' => false,
                        ];

                        $totalAmount += $messAmount;
                    }
                }

                /*
                 * Cooler charges
                 */
                $coolerEnabled = $override?->cooler_enabled
                    ?? (bool) $config->cooler_enabled;

                if ($coolerEnabled) {
                    $coolerAmount = $override?->custom_cooler;

                    if ($coolerAmount === null) {
                        $coolerAmount = $config->default_cooler_amount;
                    }

                    $coolerAmount = (float) ($coolerAmount ?? 0);

                    if ($coolerAmount > 0) {
                        $items[] = [
                            'item_type' => 'amenity',
                            'amenity_type' => 'cooler',
                            'title' => 'Cooler Charges',
                            'amount' => $coolerAmount,
                            'description' => null,
                            'is_late_fee' => false,
                        ];

                        $totalAmount += $coolerAmount;
                    }
                }

                /*
                 * Config-wide custom charges.
                 */
                foreach ($config->custom_charges ?? [] as $custom) {
                    $name = trim((string) ($custom['name'] ?? ''));
                    $amount = (float) ($custom['amount'] ?? 0);

                    if ($name === '' || $amount <= 0) {
                        continue;
                    }

                    $items[] = [
                        'item_type' => 'other',
                        'amenity_type' => 'custom',
                        'title' => $name,
                        'amount' => $amount,
                        'description' => null,
                        'is_late_fee' => false,
                    ];

                    $totalAmount += $amount;
                }

                if (empty($items) || $totalAmount <= 0) {
                    $skipped++;
                    continue;
                }

                $invoice = FeeInvoice::create([
                    'resident_id' => $resident->id,
                    'application_id' => null,
                    'stay_id' => $stay->id,
                    'monthly_config_id' => $config->id,
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'fee_type' => 'hostel_fee',
                    'amount' => round($totalAmount, 2),
                    'paid_amount' => 0,
                    'late_fee_amount' => 0,
                    'late_fee_per_day' => $config->late_fee_enabled
                        ? (float) $config->late_fee_per_day
                        : 0,
                    'late_fee_waived' => false,
                    'due_date' => $config->due_date,
                    'status' => 'pending',
                    'description' => "Monthly bill for {$config->full_label}",
                ]);

                $invoice->items()->createMany($items);

                $generated++;
            }
        });

        return redirect()
            ->route('billing.index')
            ->with(
                'success',
                "Generated {$generated} invoices. Skipped {$skipped}."
            );
    }

    // ==================== MANUAL INVOICE ====================
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'stay_id' => 'nullable|exists:resident_stays,id',
            'rent_amount' => 'nullable|numeric|min:0',
            'mess_amount' => 'nullable|numeric|min:0',
            'other_amount' => 'nullable|numeric|min:0',
            'other_title' => 'nullable|string|max:100',
            'due_date' => 'required|date',
            'description' => 'nullable|string',
            'late_fee_per_day' => ['nullable', 'numeric', 'min:0'],
        ]);

        $stayId = $validated['stay_id'] ?? ResidentStay::where('resident_id', $validated['resident_id'])
            ->where('status', 'active')
            ->value('id');

        if (!$stayId) {
            return back()->with('error', 'No active stay found for this resident.');
        }

        $items = [];
        $totalAmount = 0;

        foreach ([
            ['rent_amount', 'rent', 'rent', 'Room Rent'],
            ['mess_amount', 'mess', 'mess', 'Mess Charges'],
            ['other_amount', 'other', 'custom', $validated['other_title'] ?: 'Other Charges'],
        ] as [$field, $type, $amenity, $title]) {
            if (($validated[$field] ?? 0) > 0) {
                $items[] = [
                    'item_type' => $type,
                    'amenity_type' => $amenity,
                    'title' => $title,
                    'amount' => $validated[$field],
                ];
                $totalAmount += $validated[$field];
            }
        }

        if (empty($items)) {
            return back()->with('error', 'Please enter at least one amount.');
        }

        DB::transaction(function () use ($validated, $stayId, $items, $totalAmount) {
            $invoice = FeeInvoice::create([
                'resident_id' => $validated['resident_id'],
                'stay_id' => $stayId,
                'invoice_number' => $this->generateInvoiceNumber(),
                'fee_type' => 'hostel_fee',
                'amount' => $totalAmount,
                'paid_amount' => 0,
                'due_date' => $validated['due_date'],
                'late_fee_amount' => 0,
                'late_fee_per_day' => (float) ($validated['late_fee_per_day'] ?? 0),
                'status' => 'pending',
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($items as $item) {
                FeeInvoiceItem::create(['invoice_id' => $invoice->id, ...$item]);
            }
        });

        return back()->with('success', 'Invoice created successfully.');
    }

    // ==================== RECORD PAYMENT WITH PROOF ====================
    public function recordPayment(
        Request $request,
        FeeInvoice $invoice
    ): RedirectResponse {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => [
                'required',
                'in:cash,upi,card,bank_transfer,other',
            ],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'proofs' => ['nullable', 'array'],
            'proofs.*' => ['image', 'max:5120'],
        ]);

        $paymentDate = Carbon::parse(
            $validated['payment_date']
        )->startOfDay();

        $maximumPayment = $invoice->balanceDueOn($paymentDate);
        $paymentAmount = (float) $validated['amount'];

        if ($paymentAmount > $maximumPayment) {
            throw ValidationException::withMessages([
                'amount' => 'Payment cannot exceed ₹'
                    . number_format($maximumPayment, 2)
                    . ' for the selected payment date.',
            ]);
        }

        DB::transaction(function () use ($validated, $invoice, $request, $paymentDate, $paymentAmount) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'resident_id' => $invoice->resident_id,
                'application_id' => $invoice->application_id,
                'amount' => $paymentAmount,
                'payment_mode' => $validated['payment_mode'],
                'transaction_id' => $validated['transaction_id'] ?? null,
                'payment_date' => $paymentDate->toDateString(),
                'notes' => $validated['notes'] ?? null,
                'receipt_number' => 'RCPT-' . now()->format('Ymd') . '-' . str_pad((string) (Payment::count() + 1), 5, '0', STR_PAD_LEFT),
            ]);

            if ($request->hasFile('proofs')) {
                foreach ($request->file('proofs') as $file) {
                    $path = $file->store(
                        'payment_proofs',
                        'public'
                    );

                    PaymentProof::create([
                        'payment_id' => $payment->id,
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }
            }

            $invoice->update([
                'paid_amount' =>
                    (float) $invoice->paid_amount + $paymentAmount,
            ]);

            $invoice->refresh();
            $invoice->load('payments');

            $invoice->freezeLateFeeIfSettled($paymentDate);

            $invoice->update([
                'status' => $invoice->computed_status,
            ]);
        });

        return back()->with(
            'success',
            'Payment recorded successfully.'
        );
    }

    // ==================== WAIVE LATE FEE ====================
    public function waiveLateFee(Request $request, FeeInvoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($invoice->effective_late_fee_amount <= 0) {
            return back()->with('error', 'No late fee to waive.');
        }

        if ($invoice->paid_amount < $invoice->amount) {
            return back()->with('error', 'Core amount must be paid before waiving late fee.');
        }

        $invoice->update([
            'late_fee_waived' => true,
            'waived_by' => Auth::id(),
            'waived_at' => now(),
            'waive_reason' => $validated['reason'],
        ]);

        // Remove late fee item
        $invoice->items()->where('is_late_fee', true)->delete();

        // If now fully paid
        if ($invoice->paid_amount >= $invoice->amount) {
            $invoice->update(['status' => 'paid']);
        }

        return back()->with('success', 'Late fee waived successfully.');
    }

    // ==================== DESTROY ====================
    public function destroy(FeeInvoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return back()->with(
            'success',
            'Invoice moved to deleted invoices.'
        );
    }

    public function restore(int $invoice): RedirectResponse
    {
        $feeInvoice = FeeInvoice::onlyTrashed()->findOrFail($invoice);

        $feeInvoice->restore();

        return back()->with(
            'success',
            'Invoice restored successfully.'
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

    // ==================== HELPERS ====================
    private function generateInvoiceNumber(): string
    {
        return 'INV-' . now()->format('Ym') . '-' . str_pad((string) (FeeInvoice::count() + 1), 5, '0', STR_PAD_LEFT);
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
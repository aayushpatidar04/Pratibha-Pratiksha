<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\Payment;
use App\Models\Resident;
use App\Models\ResidentStay;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $query = FeeInvoice::with(['resident', 'payments', 'items']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($residentId = $request->integer('resident_id')) {
            $query->where('resident_id', $residentId);
        }

        $invoices = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'totalAmount' => (float) FeeInvoice::sum('amount'),
            'paidAmount' => (float) FeeInvoice::sum('paid_amount'),
            'pendingCount' => FeeInvoice::whereIn('status', ['pending', 'partial'])->count(),
            'overdueCount' => FeeInvoice::where('status', 'overdue')->count(),
        ];

        return Inertia::render('Billing/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => $request->only('status', 'resident_id'),
            'residents' => Resident::where('status', 'active')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'resident_code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'stay_id' => 'nullable|exists:resident_stays,id',
            'rent_amount' => 'nullable|numeric|min:0',
            'mess_amount' => 'nullable|numeric|min:0',
            'caution_money' => 'nullable|numeric|min:0',
            'other_amount' => 'nullable|numeric|min:0',
            'other_title' => 'nullable|string|max:100',
            'due_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $stayId = $validated['stay_id'] ?? null;

        if (!$stayId) {
            $stay = ResidentStay::where('resident_id', $validated['resident_id'])
                ->where('status', 'active')
                ->first();

            $stayId = $stay?->id;
        }

        if (!$stayId) {
            return back()->with('error', 'This resident has no active stay/room assigned. Assign a room before creating an invoice.');
        }

        $items = [];

        if (($validated['rent_amount'] ?? 0) > 0) {
            $items[] = [
                'item_type' => 'rent',
                'title' => 'Room Rent',
                'amount' => $validated['rent_amount'],
            ];
        }

        if (($validated['mess_amount'] ?? 0) > 0) {
            $items[] = [
                'item_type' => 'mess',
                'title' => 'Mess Charges',
                'amount' => $validated['mess_amount'],
            ];
        }

        if (($validated['caution_money'] ?? 0) > 0) {
            $items[] = [
                'item_type' => 'caution_money',
                'title' => 'Caution Money',
                'amount' => $validated['caution_money'],
            ];
        }

        if (($validated['other_amount'] ?? 0) > 0) {
            $items[] = [
                'item_type' => 'other',
                'title' => $validated['other_title'] ?: 'Other Charges',
                'amount' => $validated['other_amount'],
            ];
        }

        if (empty($items)) {
            return back()->with('error', 'Please enter at least one amount.');
        }

        $totalAmount = collect($items)->sum('amount');

        DB::transaction(function () use ($validated, $stayId, $items, $totalAmount) {
            $invoice = FeeInvoice::create([
                'resident_id' => $validated['resident_id'],
                'stay_id' => $stayId,
                'invoice_number' => $this->generateInvoiceNumber(),
                'fee_type' => 'hostel_fee',
                'amount' => $totalAmount,
                'paid_amount' => 0,
                'due_date' => $validated['due_date'],
                'status' => 'pending',
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($items as $item) {
                FeeInvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => $item['item_type'],
                    'title' => $item['title'],
                    'amount' => $item['amount'],
                    'description' => null,
                ]);
            }
        });

        return back()->with('success', 'Invoice created successfully.');
    }

    public function recordPayment(Request $request, FeeInvoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:cash,upi,card,bank_transfer,other',
            'transaction_id' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'resident_id' => $invoice->resident_id,
            'amount' => $validated['amount'],
            'payment_mode' => $validated['payment_mode'],
            'transaction_id' => $validated['transaction_id'] ?? null,
            'payment_date' => $validated['payment_date'],
            'notes' => $validated['notes'] ?? null,
            'receipt_number' => 'RCPT-' . now()->format('Ymd') . '-' . str_pad((string) (Payment::count() + 1), 5, '0', STR_PAD_LEFT),
        ]);

        $newPaid = $invoice->paid_amount + $validated['amount'];

        $invoice->update([
            'paid_amount' => $newPaid,
            'status' => $newPaid >= $invoice->amount ? 'paid' : 'partial',
        ]);

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function destroy(FeeInvoice $invoice): RedirectResponse
    {
        $invoice->payments()->delete();
        $invoice->items()->delete();
        $invoice->delete();

        return back()->with('success', 'Invoice deleted.');
    }

    private function generateInvoiceNumber(): string
    {
        return 'INV-' . now()->format('Ym') . '-' . str_pad((string) (FeeInvoice::count() + 1), 5, '0', STR_PAD_LEFT);
    }

    public function exportPdfEnglish(FeeInvoice $invoice)
    {
        $invoice->load(['resident', 'items', 'payments']);

        $pdf = Pdf::loadView('pdf.invoices.english', [
            'invoice' => $invoice,
        ])->setPaper('A5', 'portrait');

        return $pdf->stream($invoice->invoice_number . '-english.pdf');
    }

    public function exportPdfHindi(FeeInvoice $invoice)
    {
        $invoice->load(['resident', 'items', 'payments']);

        $pdf = Pdf::loadView('pdf.invoices.hindi', [
            'invoice' => $invoice,
        ])->setPaper('A5', 'portrait');

        return $pdf->stream($invoice->invoice_number . '-hindi.pdf');
    }
}
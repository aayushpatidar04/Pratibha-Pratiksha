<?php

namespace App\Services;

use App\Models\FeeInvoice;
use App\Models\ResidentStay;
use Illuminate\Support\Facades\DB;

class SecurityDepositBillingService
{
    public function createInvoice(ResidentStay $stay): ?FeeInvoice
    {
        $depositAmount = (float) $stay->deposit_amount;

        if ($depositAmount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($stay, $depositAmount) {
            $invoice = FeeInvoice::firstOrNew([
                'resident_id' => $stay->resident_id,
                'stay_id' => $stay->id,
                'fee_type' => 'security_deposit',
            ]);

            if (!$invoice->exists) {
                $invoice->invoice_number =
                    $this->generateInvoiceNumber();
            }

            $invoice->fill([
                'application_id' => null,
                'monthly_config_id' => null,

                'amount' => $depositAmount,
                'paid_amount' => $invoice->paid_amount ?? 0,

                'late_fee_amount' => 0,
                'late_fee_waived' => false,

                'due_date' => $stay->check_in_date ?? now(),
                'status' => $invoice->exists
                    ? $invoice->computed_status
                    : 'pending',

                'description' =>
                    'Refundable security deposit for stay #'
                    . $stay->id,
            ]);

            $invoice->save();

            $invoice->items()->updateOrCreate(
                [
                    'item_type' => 'security_deposit',
                    'amenity_type' => null,
                ],
                [
                    'title' => 'Refundable Security Deposit',
                    'amount' => $depositAmount,
                    'description' =>
                        'One-time refundable deposit for this stay.',
                    'is_late_fee' => false,
                ]
            );

            return $invoice->fresh('items');
        });
    }

    private function generateInvoiceNumber(): string
    {
        // Get the latest invoice including trashed ones
        $lastInvoice = FeeInvoice::withTrashed()
            ->orderBy('id', 'desc')
            ->first();

        // Default start number
        $nextNumber = 1;

        if ($lastInvoice) {
            // Extract the numeric part from invoice_number
            // Assuming format: INV-YYYYMM-00001
            $parts = explode('-', $lastInvoice->invoice_number);
            $lastNumeric = isset($parts[2]) ? (int)$parts[2] : 0;

            $nextNumber = $lastNumeric + 1;
        }

        return 'INV-' . now()->format('Ym') . '-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
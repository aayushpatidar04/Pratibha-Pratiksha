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
        return 'INV-'
            . now()->format('Ym')
            . '-'
            . str_pad(
                (string) (
                    FeeInvoice::withTrashed()->count() + 1
                ),
                5,
                '0',
                STR_PAD_LEFT
            );
    }
}
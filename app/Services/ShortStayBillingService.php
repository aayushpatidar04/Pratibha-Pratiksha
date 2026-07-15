<?php

namespace App\Services;

use App\Models\FeeInvoice;
use App\Models\ResidentStay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShortStayBillingService
{
    public function createOrUpdateInvoice(
        ResidentStay $stay,
        ?Carbon $checkoutDate = null
    ): FeeInvoice {
        if ($stay->billing_basis !== 'daily') {
            throw ValidationException::withMessages([
                'billing_basis' => 'This stay is not configured for daily billing.',
            ]);
        }

        $checkIn = Carbon::parse($stay->check_in_date)->startOfDay();

        $checkOut = $checkoutDate
            ?? $stay->actual_check_out_date
            ?? $stay->expected_check_out_date;

        if (!$checkOut) {
            throw ValidationException::withMessages([
                'expected_check_out_date' =>
                    'Expected checkout date is required for daily billing.',
            ]);
        }

        $checkOut = Carbon::parse($checkOut)->startOfDay();

        if ($checkOut->lt($checkIn)) {
            throw ValidationException::withMessages([
                'expected_check_out_date' =>
                    'Checkout date cannot be before check-in date.',
            ]);
        }

        $dailyRate = (float) ($stay->daily_rate ?: 350);

        $numberOfDays = $checkIn->diffInDays($checkOut) + 1;

        $amount = round($numberOfDays * $dailyRate, 2);

        return DB::transaction(function () use ($stay, $checkIn, $checkOut, $dailyRate, $numberOfDays, $amount) {
            $invoice = $stay->shortStayInvoice;

            if (!$invoice) {
                $invoice = FeeInvoice::create([
                    'resident_id' => $stay->resident_id,
                    'application_id' => null,
                    'stay_id' => $stay->id,
                    'monthly_config_id' => null,
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'fee_type' => 'short_stay',
                    'amount' => $amount,
                    'paid_amount' => 0,
                    'late_fee_amount' => 0,
                    'late_fee_waived' => false,
                    'due_date' => $checkOut->toDateString(),
                    'status' => 'pending',
                    'description' =>
                        "Short stay accommodation from "
                        . $checkIn->format('d-m-Y')
                        . " to "
                        . $checkOut->format('d-m-Y'),
                ]);

                $stay->update([
                    'short_stay_invoice_id' => $invoice->id,
                ]);
            } else {
                /*
                 * Do not reduce the invoice below money already received.
                 */
                if ($amount < (float) $invoice->paid_amount) {
                    throw ValidationException::withMessages([
                        'actual_check_out_date' =>
                            'The recalculated invoice amount is lower than the amount already paid. Process a refund or adjustment first.',
                    ]);
                }

                $invoice->update([
                    'amount' => $amount,
                    'due_date' => $checkOut->toDateString(),
                    'description' =>
                        "Short stay accommodation from "
                        . $checkIn->format('d-m-Y')
                        . " to "
                        . $checkOut->format('d-m-Y'),
                ]);
            }

            $invoice->items()->updateOrCreate(
                [
                    'item_type' => 'short_stay',
                    'amenity_type' => 'accommodation',
                ],
                [
                    'title' => 'Short Stay Accommodation',
                    'amount' => $amount,
                    'description' =>
                        "{$numberOfDays} day(s) × ₹"
                        . number_format($dailyRate, 2)
                        . " from "
                        . $checkIn->format('d-m-Y')
                        . " to "
                        . $checkOut->format('d-m-Y'),
                    'is_late_fee' => false,
                ]
            );

            $invoice->refresh();

            $invoice->update([
                'status' => $invoice->computed_status,
            ]);

            return $invoice;
        });
    }

    private function generateInvoiceNumber(): string
    {
        return 'INV-'
            . now()->format('Ym')
            . '-'
            . str_pad(
                (string) (FeeInvoice::withTrashed()->count() + 1),
                5,
                '0',
                STR_PAD_LEFT
            );
    }
}
<?php

namespace App\Services;

use App\Models\CheckoutRequest;
use App\Models\FeeInvoice;
use App\Models\Payment;
use App\Models\PaymentProof;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SecurityDepositRefundService
{
    public function getRefundDetails(
        CheckoutRequest $checkoutRequest
    ): array {
        $securityDeposit = FeeInvoice::query()
            ->where('resident_id', $checkoutRequest->resident_id)
            ->where('fee_type', 'security_deposit')
            ->whereNull('deleted_at')
            ->latest('id')
            ->first();

        if (!$securityDeposit) {
            throw ValidationException::withMessages([
                'refund' =>
                    'No security deposit invoice was found for this stay.',
            ]);
        }

        $securityDepositAmount = round(
            (float) $securityDeposit->amount,
            2
        );

        $shortNoticeCharge = round(
            (float) $checkoutRequest->short_notice_charge_final,
            2
        );

        $assetDamageCharge = round(
            (float) $checkoutRequest->asset_damage_charge,
            2
        );

        $outstandingDuesDeduction = round(
            (float) $checkoutRequest->outstanding_dues_deduction,
            2
        );

        $otherCheckoutCharge = round(
            (float) $checkoutRequest->other_checkout_charge,
            2
        );

        $totalDeductions = round(
            $shortNoticeCharge
            + $assetDamageCharge
            + $outstandingDuesDeduction
            + $otherCheckoutCharge,
            2
        );

        $refundAmount = max(
            0,
            round(
                $securityDepositAmount
                - $totalDeductions,
                2
            )
        );

        return [
            'invoice' => $securityDeposit,

            'security_deposit_amount' =>
                $securityDepositAmount,

            'short_notice_charge' =>
                $shortNoticeCharge,

            'asset_damage_charge' =>
                $assetDamageCharge,

            'outstanding_dues_deduction' =>
                $outstandingDuesDeduction,

            'other_checkout_charge' =>
                $otherCheckoutCharge,

            'total_deductions' =>
                $totalDeductions,

            'refund_amount' =>
                $refundAmount,

            'refund_status' =>
                $securityDeposit->refund_status,
        ];
    }

    public function refund(
        CheckoutRequest $checkoutRequest,
        string $transactionId,
        ?string $refundNotes = null,
        array $proofs = []
    ): FeeInvoice {
        return DB::transaction(function () use ($checkoutRequest, $transactionId, $refundNotes, $proofs) {
            $securityDeposit = FeeInvoice::query()
                ->where('resident_id', $checkoutRequest->resident_id)
                ->where('fee_type', 'security_deposit')
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (!$securityDeposit) {
                throw ValidationException::withMessages([
                    'refund' => 'No security deposit invoice was found for this stay.',
                ]);
            }

            if ($securityDeposit->refund_status === 'refunded') {
                throw ValidationException::withMessages([
                    'refund' => 'This security deposit has already been refunded.',
                ]);
            }

            if ($checkoutRequest->status !== CheckoutRequest::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'refund' => 'Security deposit can only be refunded after checkout is completed.',
                ]);
            }

            $details = $this->getRefundDetails($checkoutRequest);
            $refundAmount = $details['refund_amount'];

            // Create a refund Payment record (negative amount, flagged as refund)
            $payment = Payment::create([
                'invoice_id' => $securityDeposit->id,
                'resident_id' => $securityDeposit->resident_id,
                'application_id' => $securityDeposit->application_id,
                'amount' => -abs($refundAmount),
                'payment_mode' => 'bank_transfer',
                'transaction_id' => $transactionId,
                'payment_date' => now()->toDateString(),
                'notes' => $refundNotes,
                'receipt_number' => 'RCPT-' . now()->format('Ymd') . '-' . str_pad((string) (Payment::count() + 1), 5, '0', STR_PAD_LEFT),
                'is_refund' => true,
            ]);

            // Attach proof uploads
            foreach ($proofs as $file) {
                $path = $file->store('payment_proofs', 'public');
                PaymentProof::create([
                    'payment_id' => $payment->id,
                    'file_path' => $path,
                    'file_type' => $file->getClientOriginalExtension(),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }

            $securityDeposit->update([
                'refund_status' => 'refunded',
                'refund_amount' => $refundAmount,
                'refunded_at' => now(),
                'refund_transaction_id' => $transactionId,
                'refund_notes' => $refundNotes,
            ]);

            return $securityDeposit->fresh()->load('payments.proofs');
        });
    }
}
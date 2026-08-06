<?php

namespace App\Http\Controllers;

use App\Models\CheckoutRequest;
use App\Services\SecurityDepositRefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityDepositRefundController extends Controller
{
    public function show(
        CheckoutRequest $checkoutRequest,
        SecurityDepositRefundService $refundService
    ): JsonResponse {
        $details = $refundService->getRefundDetails(
            $checkoutRequest
        );

        $invoice = $details['invoice'];

        return response()->json([
            'checkout_request_id' =>
                $checkoutRequest->id,

            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' =>
                    $invoice->invoice_number,
                'amount' =>
                    (float) $invoice->amount,
                'refund_status' =>
                    $invoice->refund_status,
                'refund_amount' =>
                    (float) $invoice->refund_amount,
                'refunded_at' =>
                    $invoice->refunded_at,
                'refund_transaction_id' =>
                    $invoice->refund_transaction_id,
                'refund_notes' =>
                    $invoice->refund_notes,
            ],

            'security_deposit_amount' =>
                $details['security_deposit_amount'],

            'short_notice_charge' =>
                $details['short_notice_charge'],

            'asset_damage_charge' =>
                $details['asset_damage_charge'],

            'outstanding_dues_deduction' =>
                $details['outstanding_dues_deduction'],

            'other_checkout_charge' =>
                $details['other_checkout_charge'],

            'total_deductions' =>
                $details['total_deductions'],

            'refund_amount' =>
                $details['refund_amount'],

            'refund_status' =>
                $details['refund_status'],
        ]);
    }

    public function store(
        Request $request,
        CheckoutRequest $checkoutRequest,
        SecurityDepositRefundService $refundService
    ): RedirectResponse {
        $validated = $request->validate([
            'refund_transaction_id' => [
                'required',
                'string',
                'max:150',
            ],

            'refund_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $refundService->refund(
            $checkoutRequest,
            $validated['refund_transaction_id'],
            $validated['refund_notes'] ?? null
        );

        return back()->with(
            'success',
            'Security deposit refunded successfully.'
        );
    }
}
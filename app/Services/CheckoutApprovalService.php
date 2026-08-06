<?php

namespace App\Services;

use App\Models\CheckoutRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutApprovalService
{
    public function finalApprove(
        CheckoutRequest $checkoutRequest,
        User $admin,
        array $validated
    ): CheckoutRequest {
        if (
            $checkoutRequest->status !==
            CheckoutRequest::STATUS_WARDEN_APPROVED
        ) {
            throw ValidationException::withMessages([
                'request' =>
                    'Final approval is available only after the warden approves the inspection.',
            ]);
        }

        if (
            $checkoutRequest->warden_review_status !==
            'approved'
        ) {
            throw ValidationException::withMessages([
                'request' =>
                    'The warden inspection is not approved.',
            ]);
        }

        if (
            !in_array(
                $validated['dues_clearance_status'],
                [
                    'clear',
                    'waived',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'dues_clearance_status' =>
                    'Outstanding dues must be cleared or waived before final approval.',
            ]);
        }

        return DB::transaction(function () use (
            $checkoutRequest,
            $admin,
            $validated
        ): CheckoutRequest {
            $fromStatus = $checkoutRequest->status;

            $exitToken = $checkoutRequest->exit_token
                ?: Str::upper(
                    Str::random(12)
                    . '-'
                    . $checkoutRequest->id
                );

            $tokenExpiry = $checkoutRequest
                ->requested_checkout_date
                ->copy()
                ->endOfDay()
                ->addDay();

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_READY_FOR_EXIT,

                'admin_review_status' =>
                    'approved',

                'final_approved_by' =>
                    $admin->id,

                'final_approved_at' =>
                    now(),

                'final_approval_notes' =>
                    filled(
                        $validated[
                            'final_approval_notes'
                        ] ?? null
                    )
                        ? trim(
                            $validated[
                                'final_approval_notes'
                            ]
                        )
                        : null,

                'dues_clearance_status' =>
                    $validated[
                        'dues_clearance_status'
                    ],

                'short_notice_charge_final' =>
                    (float) (
                        $validated[
                            'short_notice_charge_final'
                        ] ?? 0
                    ),

                'asset_damage_charge' =>
                    (float) (
                        $validated[
                            'asset_damage_charge'
                        ] ?? 0
                    ),

                'other_checkout_charge' =>
                    (float) (
                        $validated[
                            'other_checkout_charge'
                        ] ?? 0
                    ),

                'charge_notes' =>
                    filled(
                        $validated[
                            'charge_notes'
                        ] ?? null
                    )
                        ? trim(
                            $validated[
                                'charge_notes'
                            ]
                        )
                        : null,

                'exit_token' =>
                    $exitToken,

                'exit_token_generated_at' =>
                    now(),

                'exit_token_expires_at' =>
                    $tokenExpiry,

                'exit_token_generated_by' =>
                    $admin->id,
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                    $checkoutRequest,

                action:
                    'admin_final_approved',

                fromStatus:
                    $fromStatus,

                toStatus:
                    CheckoutRequest::STATUS_READY_FOR_EXIT,

                actor:
                    $admin,

                notes:
                    $checkoutRequest
                        ->final_approval_notes
                    ?? 'Administration approved final checkout clearance.',

                metadata: [
                    'dues_clearance_status' =>
                        $validated[
                            'dues_clearance_status'
                        ],

                    'short_notice_charge_final' =>
                        (float) (
                            $validated[
                                'short_notice_charge_final'
                            ] ?? 0
                        ),

                    'asset_damage_charge' =>
                        (float) (
                            $validated[
                                'asset_damage_charge'
                            ] ?? 0
                        ),

                    'other_checkout_charge' =>
                        (float) (
                            $validated[
                                'other_checkout_charge'
                            ] ?? 0
                        ),

                    'exit_token_expires_at' =>
                        $tokenExpiry->toDateTimeString(),
                ]
            );

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                    $checkoutRequest,

                action:
                    'exit_token_generated',

                fromStatus:
                    CheckoutRequest::STATUS_READY_FOR_EXIT,

                toStatus:
                    CheckoutRequest::STATUS_READY_FOR_EXIT,

                actor:
                    $admin,

                notes:
                    'Exit authorization token generated for gate verification.'
            );

            return $checkoutRequest->fresh([
                'inventoryReviews.inventory',
                'histories',
            ]);
        });
    }

    public function hold(
        CheckoutRequest $checkoutRequest,
        User $admin,
        string $notes
    ): CheckoutRequest {
        if (
            !in_array(
                $checkoutRequest->status,
                [
                    CheckoutRequest::STATUS_WARDEN_APPROVED,
                    CheckoutRequest::STATUS_READY_FOR_EXIT,
                    CheckoutRequest::STATUS_ON_HOLD,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'request' =>
                    'This checkout request cannot be placed on hold in its current status.',
            ]);
        }

        return DB::transaction(function () use (
            $checkoutRequest,
            $admin,
            $notes
        ): CheckoutRequest {
            $fromStatus = $checkoutRequest->status;

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_ON_HOLD,

                'admin_review_status' =>
                    'hold',

                'final_approved_by' =>
                    null,

                'final_approved_at' =>
                    null,

                'final_approval_notes' =>
                    trim($notes),

                'exit_token' =>
                    null,

                'exit_token_generated_at' =>
                    null,

                'exit_token_expires_at' =>
                    null,

                'exit_token_generated_by' =>
                    null,
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                    $checkoutRequest,

                action:
                    'final_approval_put_on_hold',

                fromStatus:
                    $fromStatus,

                toStatus:
                    CheckoutRequest::STATUS_ON_HOLD,

                actor:
                    $admin,

                notes:
                    trim($notes)
            );

            return $checkoutRequest;
        });
    }

    public function reject(
        CheckoutRequest $checkoutRequest,
        User $admin,
        string $notes
    ): CheckoutRequest {
        if (
            in_array(
                $checkoutRequest->status,
                [
                    CheckoutRequest::STATUS_COMPLETED,
                    CheckoutRequest::STATUS_CANCELLED,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'request' =>
                    'This checkout request can no longer be rejected.',
            ]);
        }

        return DB::transaction(function () use (
            $checkoutRequest,
            $admin,
            $notes
        ): CheckoutRequest {
            $fromStatus = $checkoutRequest->status;

            $checkoutRequest->update([
                'status' =>
                    CheckoutRequest::STATUS_ADMIN_REJECTED,

                'admin_review_status' =>
                    'rejected',

                'final_approved_by' =>
                    null,

                'final_approved_at' =>
                    null,

                'final_approval_notes' =>
                    trim($notes),

                'exit_token' =>
                    null,

                'exit_token_generated_at' =>
                    null,

                'exit_token_expires_at' =>
                    null,

                'exit_token_generated_by' =>
                    null,
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                    $checkoutRequest,

                action:
                    'final_admin_rejected',

                fromStatus:
                    $fromStatus,

                toStatus:
                    CheckoutRequest::STATUS_ADMIN_REJECTED,

                actor:
                    $admin,

                notes:
                    trim($notes)
            );

            return $checkoutRequest;
        });
    }

    public function regenerateExitToken(
        CheckoutRequest $checkoutRequest,
        User $admin
    ): CheckoutRequest {
        if (
            $checkoutRequest->status !==
            CheckoutRequest::STATUS_READY_FOR_EXIT
        ) {
            throw ValidationException::withMessages([
                'request' =>
                    'Exit authorization can be regenerated only for requests ready for exit.',
            ]);
        }

        return DB::transaction(function () use (
            $checkoutRequest,
            $admin
        ): CheckoutRequest {
            $checkoutRequest->update([
                'exit_token' =>
                    Str::upper(
                        Str::random(12)
                        . '-'
                        . $checkoutRequest->id
                    ),

                'exit_token_generated_at' =>
                    now(),

                'exit_token_expires_at' =>
                    $checkoutRequest
                        ->requested_checkout_date
                        ->copy()
                        ->endOfDay()
                        ->addDay(),

                'exit_token_generated_by' =>
                    $admin->id,
            ]);

            CheckoutRequestHistoryService::record(
                checkoutRequest:
                    $checkoutRequest,

                action:
                    'exit_token_regenerated',

                fromStatus:
                    CheckoutRequest::STATUS_READY_FOR_EXIT,

                toStatus:
                    CheckoutRequest::STATUS_READY_FOR_EXIT,

                actor:
                    $admin,

                notes:
                    'Exit authorization token regenerated by administration.'
            );

            return $checkoutRequest;
        });
    }
}
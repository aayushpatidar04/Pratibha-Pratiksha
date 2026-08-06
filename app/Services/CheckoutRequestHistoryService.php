<?php

namespace App\Services;

use App\Models\CheckoutRequest;
use App\Models\CheckoutRequestHistory;
use App\Models\Resident;
use App\Models\User;

class CheckoutRequestHistoryService
{
    public static function record(
        CheckoutRequest $checkoutRequest,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        User|Resident|null $actor = null,
        ?string $notes = null,
        array $metadata = []
    ): CheckoutRequestHistory {
        return CheckoutRequestHistory::create([
            'checkout_request_id' =>
                $checkoutRequest->id,

            'action' =>
                $action,

            'from_status' =>
                $fromStatus,

            'to_status' =>
                $toStatus,

            'actor_type' =>
                match (true) {
                    $actor instanceof Resident =>
                        'resident',

                    $actor instanceof User =>
                        'user',

                    default =>
                        'system',
                },

            'actor_id' =>
                $actor?->id,

            'notes' =>
                $notes,

            'metadata' =>
                $metadata ?: null,
        ]);
    }
}
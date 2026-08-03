<?php

namespace App\Services;

use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class LeaveParentApprovalService
{
    public function send(
        LeaveRequest $leave
    ): bool {
        $leave->loadMissing('resident');

        $resident = $leave->resident;

        if (!$resident) {
            return false;
        }

        $parentPhone = $this->resolveParentPhone(
            $resident
        );

        if (!$parentPhone) {
            Log::warning(
                'Parent leave approval not sent: phone unavailable.',
                [
                    'leave_id' => $leave->id,
                    'resident_id' => $leave->resident_id,
                ]
            );

            return false;
        }

        $approvalUrl = URL::temporarySignedRoute(
            'leave.parent.review',
            now()->addDays(7),
            [
                'token' =>
                    $leave->parent_approval_token,
            ]
        );

        $message = $this->buildMessage(
            $leave,
            $approvalUrl
        );

        try {
            $response = Http::withToken(
                config(
                    'services.whatsapp_gateway.token'
                )
            )
                ->timeout(20)
                ->post(
                    config(
                        'services.whatsapp_gateway.url'
                    ),
                    [
                        /*
                         * Update these keys if your gateway uses
                         * phone/message/sessionId naming.
                         */
                        'session_id' => config(
                            'services.whatsapp_gateway.session_id'
                        ),

                        'to' => $this->normalisePhone(
                            $parentPhone
                        ),

                        'message' => $message,
                    ]
                );

            if (!$response->successful()) {
                Log::error(
                    'Parent leave WhatsApp failed.',
                    [
                        'leave_id' => $leave->id,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]
                );

                return false;
            }

            $leave->forceFill([
                'parent_approval_sent_at' => now(),
            ])->save();

            return true;
        } catch (\Throwable $e) {
            report($e);

            Log::error(
                'Parent leave WhatsApp exception.',
                [
                    'leave_id' => $leave->id,
                    'message' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    protected function resolveParentPhone(
        $resident
    ): ?string {
        return $resident->father_phone
            ?: $resident->mother_phone
            ?: null;
    }

    protected function normalisePhone(
        string $phone
    ): string {
        $phone = preg_replace(
            '/\D+/',
            '',
            $phone
        );

        if (
            strlen($phone) === 10
        ) {
            return '91' . $phone;
        }

        return $phone;
    }

    protected function buildMessage(
        LeaveRequest $leave,
        string $approvalUrl
    ): string {
        $residentName = trim(
            ($leave->resident->first_name ?? '')
            . ' '
            . ($leave->resident->last_name ?? '')
        );

        return implode("\n", [
            '*Leave Approval Request*',
            '',
            "Resident: {$residentName}",
            "Resident Code: {$leave->resident->resident_code}",
            "Leave Type: {$leave->leave_type_label}",
            'From: ' . $leave->from_date->format('d-m-Y'),
            'To: ' . $leave->to_date->format('d-m-Y'),
            "Destination: " . ($leave->destination ?: '-'),
            "Reason: {$leave->reason}",
            '',
            'Please open the link below to approve or reject:',
            $approvalUrl,
            '',
            'This approval link is valid for 7 days.',
        ]);
    }
}
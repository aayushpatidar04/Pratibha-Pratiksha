<?php

namespace App\Http\Controllers;

use App\Models\CheckoutRequest;
use App\Services\CheckoutCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutGateController extends Controller
{
    public function __construct(
        protected CheckoutCompletionService
        $checkoutCompletionService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render(
            'CheckoutGate/Index',
            [
                'verifiedRequest' =>
                    null,
            ]
        );
    }

    public function verify(
        Request $request
    ): Response {
        $validated = $request->validate([
            'exit_token' => [
                'required',
                'string',
                'max:200',
            ],
        ]);

        $token = strtoupper(
            trim(
                $validated['exit_token']
            )
        );

        $checkoutRequest =
            $this->checkoutCompletionService
                ->verifyToken($token);

        return Inertia::render(
            'CheckoutGate/Index',
            [
                'verifiedRequest' =>
                    $this->transformRequest(
                        $checkoutRequest
                    ),

                'searchedToken' =>
                    $token,
            ]
        );
    }

    public function complete(
        Request $request,
        CheckoutRequest $checkoutRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'exit_token' => [
                'required',
                'string',
                'max:200',
            ],

            'gate_verification_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'completion_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'confirmed_resident_identity' => [
                'accepted',
            ],

            'confirmed_physical_exit' => [
                'accepted',
            ],
        ]);

        /*
         * Verify the submitted token still matches the record.
         */
        if (
            !hash_equals(
                (string) $checkoutRequest
                    ->exit_token,
                trim(
                    $validated[
                        'exit_token'
                    ]
                )
            )
        ) {
            throw ValidationException::withMessages([
                'exit_token' =>
                    'The submitted exit code does not match this checkout request.',
            ]);
        }

        $this->checkoutCompletionService
            ->complete(
                $checkoutRequest,
                $request->user(),
                $validated
            );

        return redirect()
            ->route(
                'checkout-gate.index'
            )
            ->with(
                'success',
                'Resident checkout completed successfully. The room and bed have been released.'
            );
    }

    protected function transformRequest(
        CheckoutRequest $request
    ): array {
        return [
            'id' =>
                $request->id,

            'status' =>
                $request->status,

            'requested_checkout_date' =>
                $request
                    ->requested_checkout_date
                        ?->toDateString(),

            'exit_token_expires_at' =>
                $request
                    ->exit_token_expires_at,

            'dues_clearance_status' =>
                $request
                    ->dues_clearance_status,

            'total_checkout_charges' =>
                $request
                    ->totalCheckoutCharges(),

            'resident' => [
                'id' =>
                    $request->resident?->id,

                'name' =>
                    trim(
                        ($request->resident
                                ?->first_name ?? '')
                        . ' '
                        . ($request->resident
                                ?->last_name ?? '')
                    ),

                'resident_code' =>
                    $request->resident
                            ?->resident_code,

                'phone' =>
                    $request->resident?->phone,

                'photo_url' =>
                    $request->resident
                            ?->photo_url,

                'status' =>
                    $request->resident
                            ?->status,
            ],

            'stay' => [
                'id' =>
                    $request->stay?->id,

                'building' =>
                    $request->stay
                        ?->building?->name,

                'floor' =>
                    $request->stay
                        ?->floor?->name
                    ?? $request->stay
                        ?->floor
                            ?->floor_number,

                'room' =>
                    $request->stay
                        ?->room
                            ?->room_number,

                'bed' =>
                    $request->stay
                        ?->bed
                            ?->bed_number,

                'check_in_date' =>
                    $request->stay
                        ?->check_in_date
                            ?->toDateString(),
            ],

            'final_approver' =>
                $request->finalApprover
                ? [
                    'name' =>
                        $request
                            ->finalApprover
                            ->name,
                ]
                : null,
        ];
    }
}
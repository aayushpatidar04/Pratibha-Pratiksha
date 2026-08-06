<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Document;
use App\Models\EmergencyAlert;
use App\Models\FeeInvoice;
use App\Models\KycRequirement;
use App\Models\LeaveRequest;
use App\Models\MessMenu;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Resident;
use App\Models\RoomChangeRequest;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Resident|null $resident */
        $resident = $request->user('resident');

        abort_unless($resident, 401);

        $resident->load([
            'currentStay.building:id,name',
            'currentStay.floor:id,name,floor_number',
            'currentStay.room:id,room_number',
            'currentStay.bed:id,bed_number',
        ]);

        $currentStay = $resident->currentStay;

        /*
         * --------------------------------------------------------------
         * Billing
         * --------------------------------------------------------------
         */
        $invoiceQuery = FeeInvoice::query()
            ->where('resident_id', $resident->id);

        $outstandingInvoices = (clone $invoiceQuery)
            ->whereIn('status', [
                'unpaid',
                'partial',
                'overdue',
            ])
            ->get();

        $outstandingAmount = $outstandingInvoices
            ->sum(
                fn(FeeInvoice $invoice) =>
                max(
                    0,
                    (float) $invoice->amount
                    - (float) $invoice->paid_amount
                )
            );

        $nextDueInvoice = (clone $invoiceQuery)
            ->whereIn('status', [
                'unpaid',
                'partial',
                'overdue',
            ])
            ->orderByRaw(
                'CASE WHEN due_date IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy('due_date')
            ->first([
                'id',
                'invoice_number',
                'amount',
                'paid_amount',
                'due_date',
                'status',
            ]);

        $recentInvoices = (clone $invoiceQuery)
            ->latest('created_at')
            ->limit(5)
            ->get([
                'id',
                'invoice_number',
                'fee_type',
                'description',
                'amount',
                'paid_amount',
                'status',
                'due_date',
                'created_at',
            ])
            ->map(function (FeeInvoice $invoice): array {
                return [
                    'id' => $invoice->id,
                    'invoice_number' =>
                        $invoice->invoice_number,
                    'fee_type' =>
                        $invoice->fee_type,
                    'description' =>
                        $invoice->description,
                    'amount' =>
                        (float) $invoice->amount,
                    'paid_amount' =>
                        (float) $invoice->paid_amount,
                    'balance' =>
                        max(
                            0,
                            (float) $invoice->amount
                            - (float) $invoice->paid_amount
                        ),
                    'status' =>
                        $invoice->status,
                    'due_date' =>
                        $invoice->due_date,
                    'created_at' =>
                        $invoice->created_at,
                ];
            });

        $recentPayments = Payment::query()
            ->where('resident_id', $resident->id)
            ->latest('payment_date')
            ->latest('id')
            ->limit(5)
            ->get([
                'id',
                'invoice_id',
                'amount',
                'payment_mode',
                'transaction_id',
                'payment_date',
                'receipt_number',
            ])
            ->map(function (Payment $payment): array {
                return [
                    'id' => $payment->id,
                    'invoice_id' =>
                        $payment->invoice_id,
                    'amount' =>
                        (float) $payment->amount,
                    'payment_mode' =>
                        $payment->payment_mode,
                    'transaction_id' =>
                        $payment->transaction_id,
                    'payment_date' =>
                        $payment->payment_date,
                    'receipt_number' =>
                        $payment->receipt_number,
                ];
            });

        /*
         * --------------------------------------------------------------
         * Leaves
         * --------------------------------------------------------------
         */
        $pendingLeavesCount = LeaveRequest::query()
            ->where('resident_id', $resident->id)
            ->whereIn('final_status', [
                'pending',
                'parent_approval_pending',
            ])
            ->count();

        $activeLeave = LeaveRequest::query()
            ->where('resident_id', $resident->id)
            ->where('final_status', 'approved')
            ->whereDate('from_date', '<=', today())
            ->whereDate('to_date', '>=', today())
            ->latest('from_date')
            ->first([
                'id',
                'leave_type',
                'from_date',
                'to_date',
                'destination',
                'gate_pass_code',
                'final_status',
            ]);

        $latestLeave = LeaveRequest::query()
            ->where('resident_id', $resident->id)
            ->latest('created_at')
            ->first([
                'id',
                'leave_type',
                'from_date',
                'to_date',
                'destination',
                'final_status',
                'parent_approval_status',
                'admin_approval_status',
                'created_at',
            ]);

        /*
         * --------------------------------------------------------------
         * Complaints
         * --------------------------------------------------------------
         */
        $openComplaintsCount = Complaint::query()
            ->where('resident_id', $resident->id)
            ->whereIn('status', [
                'open',
                'in_progress',
                'escalated',
            ])
            ->count();

        $latestComplaint = Complaint::query()
            ->where('resident_id', $resident->id)
            ->latest('created_at')
            ->first([
                'id',
                'category',
                'priority',
                'title',
                'status',
                'created_at',
            ]);

        /*
         * --------------------------------------------------------------
         * Room-change requests
         * --------------------------------------------------------------
         */
        $pendingRoomRequestsCount =
            RoomChangeRequest::query()
                ->where('resident_id', $resident->id)
                ->where('status', 'pending')
                ->count();

        $latestRoomRequest =
            RoomChangeRequest::query()
                ->where('resident_id', $resident->id)
                ->with([
                    'requestedBuilding:id,name',
                    'requestedFloor:id,name,floor_number',
                    'requestedRoom:id,room_number',
                    'requestedBed:id,bed_number',
                ])
                ->latest('created_at')
                ->first();

        /*
         * --------------------------------------------------------------
         * Emergency
         * --------------------------------------------------------------
         */
        $activeEmergency = EmergencyAlert::query()
            ->where('resident_id', $resident->id)
            ->whereIn('status', [
                'active',
                'escalated',
            ])
            ->latest('created_at')
            ->first([
                'id',
                'category',
                'description',
                'location',
                'status',
                'created_at',
            ]);

        /*
         * --------------------------------------------------------------
         * Notices
         * --------------------------------------------------------------
         */
        $visibleNoticesQuery = Notice::query()
            ->published()
            ->visibleToResident($resident);

        $unreadNoticeCount =
            (clone $visibleNoticesQuery)
                ->whereDoesntHave(
                    'reads',
                    fn(Builder $query) =>
                    $query->where(
                        'resident_id',
                        $resident->id
                    )
                )
                ->count();

        $pendingAcknowledgements =
            (clone $visibleNoticesQuery)
                ->where(
                    'requires_acknowledgement',
                    true
                )
                ->where(function (Builder $query) use ($resident): void {
                    $query
                        ->whereDoesntHave(
                            'reads',
                            fn(Builder $readQuery) =>
                            $readQuery->where(
                                'resident_id',
                                $resident->id
                            )
                        )
                        ->orWhereHas(
                            'reads',
                            fn(Builder $readQuery) =>
                            $readQuery
                                ->where(
                                    'resident_id',
                                    $resident->id
                                )
                                ->whereNull(
                                    'acknowledged_at'
                                )
                        );
                })
                ->count();

        $recentNotices =
            (clone $visibleNoticesQuery)
                ->with([
                    'reads' => fn($query) =>
                        $query->where(
                            'resident_id',
                            $resident->id
                        ),
                ])
                ->orderByDesc('is_pinned')
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->limit(4)
                ->get()
                ->map(function (Notice $notice): array {
                    $read = $notice->reads->first();

                    return [
                        'id' => $notice->id,
                        'title' => $notice->title,
                        'summary' => $notice->summary,
                        'category' => $notice->category,
                        'priority' => $notice->priority,
                        'is_pinned' =>
                            (bool) $notice->is_pinned,
                        'requires_acknowledgement' =>
                            (bool) $notice
                                ->requires_acknowledgement,
                        'published_at' =>
                            $notice->published_at
                            ?? $notice->created_at,
                        'is_read' => (bool) $read,
                        'is_acknowledged' =>
                            (bool) $read?->acknowledged_at,
                    ];
                });

        /*
         * --------------------------------------------------------------
         * Today's mess menu
         * --------------------------------------------------------------
         */
        $buildingId = $currentStay?->building_id;
        $today = today()->toDateString();

        $todayMenus = MessMenu::query()
            ->whereDate('menu_date', $today)
            ->where(function (Builder $query) use ($buildingId): void {
                if ($buildingId) {
                    $query
                        ->where(
                            'building_id',
                            $buildingId
                        )
                        ->orWhereNull('building_id');

                    return;
                }

                $query->whereNull('building_id');
            })
            ->orderByRaw(
                'CASE WHEN building_id IS NULL THEN 2 ELSE 1 END'
            )
            ->orderByRaw("
                CASE meal_type
                    WHEN 'breakfast' THEN 1
                    WHEN 'lunch' THEN 2
                    WHEN 'snacks' THEN 3
                    WHEN 'dinner' THEN 4
                    ELSE 5
                END
            ")
            ->get()
            ->unique('meal_type')
            ->values()
            ->map(
                fn(MessMenu $menu) => [
                    'id' => $menu->id,
                    'meal_type' =>
                        $menu->meal_type,
                    'items' =>
                        $menu->items,
                    'special_notes' =>
                        $menu->special_notes,
                ]
            );

        /*
         * --------------------------------------------------------------
         * KYC
         * --------------------------------------------------------------
         */
        $requiredTypes = KycRequirement::query()
            ->where('is_active', true)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->pluck('document_type');

        $requiredDocuments = Document::query()
            ->where('resident_id', $resident->id)
            ->whereIn(
                'document_type',
                $requiredTypes
            )
            ->get();

        $documentsByType =
            $requiredDocuments->keyBy(
                'document_type'
            );

        $requiredDocumentCount =
            $requiredTypes->count();

        $uploadedDocumentCount =
            $requiredTypes
                ->filter(
                    fn(string $type) =>
                    $documentsByType->has($type)
                )
                ->count();

        $verifiedDocumentCount =
            $requiredTypes
                ->filter(
                    fn(string $type) =>
                    $documentsByType
                        ->get($type)
                            ?->verification_status
                    === 'verified'
                )
                ->count();

        $rejectedDocumentCount =
            $requiredTypes
                ->filter(
                    fn(string $type) =>
                    $documentsByType
                        ->get($type)
                            ?->verification_status
                    === 'rejected'
                )
                ->count();

        $kycStatus = match (true) {
            $requiredDocumentCount === 0 =>
            'complete',

            $verifiedDocumentCount ===
            $requiredDocumentCount =>
            'complete',

            $uploadedDocumentCount <
            $requiredDocumentCount =>
            'incomplete',

            default =>
            'pending_verification',
        };

        /*
         * --------------------------------------------------------------
         * Vehicles
         * --------------------------------------------------------------
         */
        $vehicleCount = Vehicle::query()
            ->where('resident_id', $resident->id)
            ->count();

        return Inertia::render(
            'ResidentPortal/Dashboard',
            [
                'resident' => [
                    'id' => $resident->id,
                    'resident_code' =>
                        $resident->resident_code,
                    'first_name' =>
                        $resident->first_name,
                    'last_name' =>
                        $resident->last_name,
                    'name' => trim(
                        $resident->first_name . ' ' .
                        $resident->last_name
                    ),
                    'photo_url' =>
                        $resident->photo_url,
                    'status' =>
                        $resident->status,
                    'course' =>
                        $resident->course,
                    'institute' =>
                        $resident->institute,
                    'must_change_password' =>
                        (bool) $resident
                            ->must_change_password,
                ],

                'currentStay' => $currentStay
                    ? [
                        'id' => $currentStay->id,
                        'building' =>
                            $currentStay
                                ->building?->name,
                        'floor' =>
                            $currentStay
                                ->floor?->name
                            ?? $currentStay
                                ->floor?->floor_number,
                        'room' =>
                            $currentStay
                                ->room?->room_number,
                        'bed' =>
                            $currentStay
                                ->bed?->bed_number,
                        'check_in_date' =>
                            $currentStay
                                ->check_in_date,
                        'expected_check_out_date' =>
                            $currentStay
                                ->expected_check_out_date,
                        'actual_check_out_date' =>
                            $currentStay
                                ->actual_check_out_date,
                        'billing_basis' =>
                            $currentStay
                                ->billing_basis,
                        'rent_amount' =>
                            (float) $currentStay
                                ->rent_amount,
                        'daily_rate' =>
                            (float) (
                                $currentStay
                                    ->daily_rate ?? 0
                            ),
                        'deposit_amount' =>
                            (float) $currentStay
                                ->deposit_amount,
                        'status' =>
                            $currentStay->status,
                    ]
                    : null,

                'billingSummary' => [
                    'outstanding_amount' =>
                        $outstandingAmount,
                    'outstanding_invoices' =>
                        $outstandingInvoices->count(),
                    'next_due_date' =>
                        $nextDueInvoice?->due_date,
                    'next_due_invoice_id' =>
                        $nextDueInvoice?->id,
                    'next_due_invoice' =>
                        $nextDueInvoice
                                ?->invoice_number,
                    'next_due_amount' =>
                        $nextDueInvoice
                        ? max(
                            0,
                            (float) $nextDueInvoice
                                ->amount
                            - (float) $nextDueInvoice
                                ->paid_amount
                        )
                        : 0,
                    'total_invoices' =>
                        (clone $invoiceQuery)->count(),
                    'paid_invoices' =>
                        (clone $invoiceQuery)
                            ->where('status', 'paid')
                            ->count(),
                ],

                'recentInvoices' =>
                    $recentInvoices,

                'recentPayments' =>
                    $recentPayments,

                'summaryCounts' => [
                    'pending_leaves' =>
                        $pendingLeavesCount,
                    'open_complaints' =>
                        $openComplaintsCount,
                    'active_emergency_alerts' =>
                        $activeEmergency ? 1 : 0,
                    'pending_requests' =>
                        $pendingRoomRequestsCount,
                    'unread_notices' =>
                        $unreadNoticeCount,
                    'acknowledgements_pending' =>
                        $pendingAcknowledgements,
                    'vehicles' =>
                        $vehicleCount,
                ],

                'leaveSummary' => [
                    'active' => $activeLeave
                        ? [
                            'id' =>
                                $activeLeave->id,
                            'leave_type' =>
                                $activeLeave
                                    ->leave_type,
                            'from_date' =>
                                $activeLeave
                                    ->from_date,
                            'to_date' =>
                                $activeLeave
                                    ->to_date,
                            'destination' =>
                                $activeLeave
                                    ->destination,
                            'gate_pass_code' =>
                                $activeLeave
                                    ->gate_pass_code,
                            'final_status' =>
                                $activeLeave
                                    ->final_status,
                        ]
                        : null,

                    'latest' => $latestLeave
                        ? [
                            'id' =>
                                $latestLeave->id,
                            'leave_type' =>
                                $latestLeave
                                    ->leave_type,
                            'from_date' =>
                                $latestLeave
                                    ->from_date,
                            'to_date' =>
                                $latestLeave
                                    ->to_date,
                            'destination' =>
                                $latestLeave
                                    ->destination,
                            'final_status' =>
                                $latestLeave
                                    ->final_status,
                            'parent_approval_status' =>
                                $latestLeave
                                    ->parent_approval_status,
                            'admin_approval_status' =>
                                $latestLeave
                                    ->admin_approval_status,
                            'created_at' =>
                                $latestLeave
                                    ->created_at,
                        ]
                        : null,
                ],

                'latestComplaint' =>
                    $latestComplaint
                    ? [
                        'id' =>
                            $latestComplaint->id,
                        'category' =>
                            $latestComplaint
                                ->category,
                        'priority' =>
                            $latestComplaint
                                ->priority,
                        'title' =>
                            $latestComplaint->title,
                        'status' =>
                            $latestComplaint->status,
                        'created_at' =>
                            $latestComplaint
                                ->created_at,
                    ]
                    : null,

                'latestRoomRequest' =>
                    $latestRoomRequest
                    ? [
                        'id' =>
                            $latestRoomRequest->id,
                        'status' =>
                            $latestRoomRequest
                                ->status,
                        'reason' =>
                            $latestRoomRequest
                                ->reason,
                        'building' =>
                            $latestRoomRequest
                                ->requestedBuilding
                                    ?->name,
                        'floor' =>
                            $latestRoomRequest
                                ->requestedFloor
                                    ?->name
                            ?? $latestRoomRequest
                                ->requestedFloor
                                    ?->floor_number,
                        'room' =>
                            $latestRoomRequest
                                ->requestedRoom
                                    ?->room_number,
                        'bed' =>
                            $latestRoomRequest
                                ->requestedBed
                                    ?->bed_number,
                        'admin_notes' =>
                            $latestRoomRequest
                                ->admin_notes,
                        'created_at' =>
                            $latestRoomRequest
                                ->created_at,
                    ]
                    : null,

                'activeEmergency' =>
                    $activeEmergency
                    ? [
                        'id' =>
                            $activeEmergency->id,
                        'category' =>
                            $activeEmergency
                                ->category,
                        'description' =>
                            $activeEmergency
                                ->description,
                        'location' =>
                            $activeEmergency
                                ->location,
                        'status' =>
                            $activeEmergency
                                ->status,
                        'created_at' =>
                            $activeEmergency
                                ->created_at,
                    ]
                    : null,

                'recentNotices' =>
                    $recentNotices,

                'todayMenus' =>
                    $todayMenus,

                'kycSummary' => [
                    'status' => $kycStatus,
                    'required' =>
                        $requiredDocumentCount,
                    'uploaded' =>
                        $uploadedDocumentCount,
                    'verified' =>
                        $verifiedDocumentCount,
                    'rejected' =>
                        $rejectedDocumentCount,
                    'missing' =>
                        max(
                            0,
                            $requiredDocumentCount
                            - $uploadedDocumentCount
                        ),
                    'percentage' =>
                        $requiredDocumentCount > 0
                        ? round(
                            (
                                $verifiedDocumentCount
                                / $requiredDocumentCount
                            ) * 100
                        )
                        : 100,
                ],
            ]
        );
    }
}
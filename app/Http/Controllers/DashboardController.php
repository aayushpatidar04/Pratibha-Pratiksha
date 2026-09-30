<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Building;
use App\Models\CheckoutRequest;
use App\Models\Complaint;
use App\Models\DisciplinaryAction;
use App\Models\EmergencyAlert;
use App\Models\FeeInvoice;
use App\Models\GatePass;
use App\Models\LeaveRequest;
use App\Models\Notice;
use App\Models\RegistrationApplication;
use App\Models\Resident;
use App\Models\ResidentStay;
use App\Models\Room;
use App\Models\RoomChangeRequest;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = now();

        $sessionStart = $today->month >= 4
            ? Carbon::create($today->year, 4, 1)->startOfDay()
            : Carbon::create($today->year - 1, 4, 1)->startOfDay();

        $sessionEnd = $sessionStart->copy()->addYear()->subDay()->endOfDay();

        $sessionName = $sessionStart->format('M Y') . ' - ' . $sessionEnd->format('M Y');

        $stats = [
            'buildings' => [
                'total' => Building::count(),
                'active' => Building::where('status', 'active')->count(),
            ],
            'rooms' => [
                'total' => Room::count(),
                'totalCapacity' => (int) Room::sum('capacity'),
                'occupiedBeds' => (int) Room::sum('occupied_beds'),
                'available' => Room::where('status', 'available')->count(),
                'occupied' => Room::where('status', 'occupied')->count(),
                'maintenance' => Room::where('status', 'maintenance')->count(),
            ],
            'residents' => [
                'total' => Resident::count(),
                'active' => Resident::where('status', 'active')->count(),
                'upcoming' => Resident::where('status', 'upcoming')->count(),
                'male' => Resident::where('gender', 'male')->count(),
                'female' => Resident::where('gender', 'female')->count(),
            ],
            'beds' => [
                'total' => Bed::count(),
                'vacant' => Bed::where('status', 'vacant')->count(),
                'occupied' => Bed::where('status', 'occupied')->count(),
            ],
            'fees' => [
                'totalAmount' => (float) FeeInvoice::sum('amount'),
                'paidAmount' => (float) FeeInvoice::sum('paid_amount'),
                'pending' => FeeInvoice::whereIn('status', ['pending', 'overdue'])->count(),
                'overdue' => FeeInvoice::where('status', 'overdue')->count(),
            ],
            'complaints' => [
                'total' => Complaint::count(),
                'open' => Complaint::whereIn('status', ['open', 'in_progress'])->count(),
                'resolved' => Complaint::where('status', 'resolved')->count(),
            ],
            'leaves' => [
                'total' => LeaveRequest::count(),
                'pending' => LeaveRequest::whereIn('final_status', ['pending', 'parent_approval_pending'])->count(),
                'approved' => LeaveRequest::where('final_status', 'approved')->count(),
            ],
            'checkouts' => [
                'pending' => CheckoutRequest::whereIn('status', [
                    'pending',
                    'under_admin_review',
                    'assigned_to_warden',
                    'warden_review_in_progress',
                    'warden_approved',
                ])->count(),
                'readyForExit' => CheckoutRequest::where('status', 'ready_for_exit')->count(),
                'completed' => CheckoutRequest::where('status', 'completed')->count(),
            ],
            'roomChanges' => [
                'pending' => RoomChangeRequest::where('status', 'pending')->count(),
                'approved' => RoomChangeRequest::where('status', 'approved')->count(),
            ],
            'notices' => [
                'published' => Notice::where('status', 'published')->count(),
                'requiresAck' => Notice::where('status', 'published')
                    ->where('requires_acknowledgement', true)
                    ->count(),
                'draft' => Notice::where('status', 'draft')->count(),
            ],
            'emergencies' => [
                'active' => EmergencyAlert::whereIn('status', ['active', 'escalated'])->count(),
                'escalated' => EmergencyAlert::where('status', 'escalated')->count(),
                'resolved' => EmergencyAlert::where('status', 'resolved')->count(),
            ],
            'applications' => [
                'pending' => RegistrationApplication::whereIn('status', ['pending', 'under_review'])
                    ->orWhere('payment_status', 'pending')
                    ->count(),
                'approved' => RegistrationApplication::where('status', 'approved')->count(),
            ],
            'disciplinary' => [
                'total' => DisciplinaryAction::count(),
            ],
            'gatePasses' => [
                'pending' => GatePass::where('status', 'pending')->count(),
                'approved' => GatePass::where('status', 'approved')->count(),
            ],
        ];

        // ── Charts data ──────────────────────────────────────────────

        $months = [];
        $current = $sessionStart->copy();

        while ($current->lte(now()->endOfMonth())) {
            $months[] = $current->copy();
            $current->addMonth();
        }

        $occupancyTrend = collect($months)->map(function ($month) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $occupied = ResidentStay::whereDate('checked_in_at', '<=', $monthEnd)
                ->where(function ($query) use ($monthStart) {
                    $query->whereNull('actual_check_out_date')
                        ->orWhereDate('actual_check_out_date', '>=', $monthStart);
                })
                ->count();

            $total = (int) Room::sum('capacity');

            return [
                'month' => $month->format('M'),
                'total' => $total,
                'occupied' => $occupied,
                'vacant' => max($total - $occupied, 0),
            ];
        });

        $sessionInvoices = FeeInvoice::whereBetween('due_date', [$sessionStart, $sessionEnd]);

        $totalAmount = (float) (clone $sessionInvoices)->sum('amount');
        $paidAmount = (float) (clone $sessionInvoices)->sum('paid_amount');
        $pendingAmount = max($totalAmount - $paidAmount, 0);

        $totalBills = (clone $sessionInvoices)->count();
        $billsProcessed = (clone $sessionInvoices)
            ->whereIn('status', ['paid', 'partial'])
            ->count();

        $refundAmount = (float) FeeInvoice::where('refund_status', 'refunded')
            ->sum('refund_amount');

        $sessionBilling = [
            'name' => $sessionName,
            'totalAmount' => $totalAmount,
            'paidAmount' => $paidAmount,
            'pendingAmount' => $pendingAmount,
            'refundAmount' => $refundAmount,
            'totalBills' => $totalBills,
            'billsProcessed' => $billsProcessed,
            'collectionRate' => $totalAmount > 0
                ? round(($paidAmount / $totalAmount) * 100)
                : 0,
        ];

        // ── Latest items for widgets ─────────────────────────────────

        $latestComplaints = Complaint::with('resident')
            ->whereIn('status', ['open', 'in_progress'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'category' => $c->category ?? $c->subject ?? 'Complaint',
                'residentName' => $c->resident
                    ? trim($c->resident->first_name . ' ' . $c->resident->last_name)
                    : '-',
                'description' => $c->description ?? $c->complaint ?? '',
                'status' => $c->status,
            ]);

        $latestLeaves = LeaveRequest::with('resident')
            ->whereIn('final_status', ['pending', 'parent_approval_pending'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($l) => [
                'id' => $l->id,
                'residentName' => $l->resident
                    ? trim($l->resident->first_name . ' ' . $l->resident->last_name)
                    : '-',
                'reason' => $l->reason ?? '-',
                'fromDate' => $l->from_date ?? $l->start_date ?? null,
                'toDate' => $l->to_date ?? $l->end_date ?? null,
                'status' => $l->final_status,
            ]);

        $latestCheckouts = CheckoutRequest::with('resident')
            ->whereIn('status', [
                'pending',
                'under_admin_review',
                'assigned_to_warden',
                'warden_review_in_progress',
                'warden_approved',
                'ready_for_exit',
            ])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'residentName' => $c->resident
                    ? trim($c->resident->first_name . ' ' . $c->resident->last_name)
                    : '-',
                'status' => $c->status,
                'createdAt' => $c->created_at,
            ]);

        $latestRoomChanges = RoomChangeRequest::with('resident')
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'residentName' => $r->resident
                    ? trim($r->resident->first_name . ' ' . $r->resident->last_name)
                    : '-',
                'reason' => $r->reason ?? '-',
                'status' => $r->status,
                'createdAt' => $r->created_at,
            ]);

        $latestNotices = Notice::where('status', 'published')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->title ?? 'Untitled Notice',
                'category' => $n->category ?? 'general',
                'priority' => $n->priority ?? 'normal',
                'publishedAt' => $n->published_at ?? $n->created_at,
                'requiresAck' => $n->requires_acknowledgement,
            ]);

        $latestEmergencies = EmergencyAlert::with('resident')
            ->whereIn('status', ['active', 'escalated'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'residentName' => $e->resident
                    ? trim($e->resident->first_name . ' ' . $e->resident->last_name)
                    : '-',
                'alertType' => $e->alert_type ?? 'Emergency',
                'description' => $e->description ?? '',
                'status' => $e->status,
                'createdAt' => $e->created_at,
            ]);

        $latestApplications = RegistrationApplication::with('resident')
            ->whereIn('status', ['pending', 'under_review'])
            ->orWhere('payment_status', 'pending')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'applicationNo' => $a->application_no ?? '-',
                'studentName' => $a->resident
                    ? trim($a->resident->first_name . ' ' . $a->resident->last_name)
                    : ($a->student_name ?? '-'),
                'status' => $a->status,
                'paymentStatus' => $a->payment_status,
                'createdAt' => $a->created_at,
            ]);

        // ── Recent activity (rich) ───────────────────────────────────

        $recentActivity = collect();

        // Recent residents
        $recentActivity = $recentActivity->merge(
            Resident::orderByDesc('created_at')
                ->limit(3)
                ->get()
                ->map(fn($r) => [
                    'id' => $r->id,
                    'name' => trim($r->first_name . ' ' . $r->last_name),
                    'action' => 'joined the hostel',
                    'date' => $r->created_at,
                    'type' => 'resident',
                    'icon' => 'Users',
                    'color' => 'green',
                ])
        );

        // Recent checkouts
        $recentActivity = $recentActivity->merge(
            CheckoutRequest::whereIn('status', ['ready_for_exit', 'completed'])
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->resident
                        ? trim($c->resident->first_name . ' ' . $c->resident->last_name)
                        : 'Resident',
                    'action' => $c->status === 'completed' ? 'checked out' : 'ready for exit',
                    'date' => $c->updated_at,
                    'type' => 'checkout',
                    'icon' => 'LogOut',
                    'color' => 'blue',
                ])
        );

        // Recent complaints resolved
        $recentActivity = $recentActivity->merge(
            Complaint::where('status', 'resolved')
                ->latest()
                ->limit(2)
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->resident
                        ? trim($c->resident->first_name . ' ' . $c->resident->last_name)
                        : 'Resident',
                    'action' => 'complaint resolved: ' . ($c->category ?? 'issue'),
                    'date' => $c->updated_at,
                    'type' => 'complaint',
                    'icon' => 'CheckCircle',
                    'color' => 'green',
                ])
        );

        // Recent leaves approved
        $recentActivity = $recentActivity->merge(
            LeaveRequest::where('final_status', 'approved')
                ->latest()
                ->limit(2)
                ->get()
                ->map(fn($l) => [
                    'id' => $l->id,
                    'name' => $l->resident
                        ? trim($l->resident->first_name . ' ' . $l->resident->last_name)
                        : 'Resident',
                    'action' => 'leave approved',
                    'date' => $l->updated_at,
                    'type' => 'leave',
                    'icon' => 'CalendarCheck',
                    'color' => 'purple',
                ])
        );

        // Sort by date descending, take top 8
        $recentActivity = $recentActivity
            ->sortByDesc('date')
            ->take(8)
            ->values()
            ->all();

        // ── MIS Reports (keep placeholder) ───────────────────────────

        $misReports = collect(range(0, 2))->map(function ($i) use ($today) {
            $month = $today->copy()->subMonths($i);

            return [
                'id' => $month->format('Y-m'),
                'label' => $month->format('F Y') . ' MIS Report',
                'generatedAt' => $month->copy()->endOfMonth()->toDateString(),
                'url' => '#',
            ];
        });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'occupancyTrend' => $occupancyTrend,
            'recentActivity' => $recentActivity,
            'sessionBilling' => $sessionBilling,
            'latestComplaints' => $latestComplaints,
            'latestLeaves' => $latestLeaves,
            'latestCheckouts' => $latestCheckouts,
            'latestRoomChanges' => $latestRoomChanges,
            'latestNotices' => $latestNotices,
            'latestEmergencies' => $latestEmergencies,
            'latestApplications' => $latestApplications,
            'misReports' => $misReports,
        ]);
    }
}
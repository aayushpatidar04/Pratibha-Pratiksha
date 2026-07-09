<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Building;
use App\Models\Complaint;
use App\Models\FeeInvoice;
use App\Models\LeaveRequest;
use App\Models\Resident;
use App\Models\ResidentStay;
use App\Models\Room;
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
                'open' => Complaint::where('status', 'open')->count(),
                'inProgress' => Complaint::where('status', 'in_progress')->count(),
                'resolved' => Complaint::where('status', 'resolved')->count(),
            ],
            'leaves' => [
                'total' => LeaveRequest::count(),
                'pending' => LeaveRequest::where('final_status', 'pending')->count(),
                'approved' => LeaveRequest::where('final_status', 'approved')->count(),
            ],
        ];

        $months = [];
        $current = $sessionStart->copy();

        while ($current->lte(now()->endOfMonth())) {
            $months[] = $current->copy();
            $current->addMonth();
        }

        $occupancyTrend = collect($months)->map(function ($month) use ($stats) {

            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $occupied = ResidentStay::whereDate('check_in_date', '<=', $monthEnd)
                ->where(function ($query) use ($monthStart) {
                    $query->whereNull('actual_check_out_date')
                        ->orWhereDate('actual_check_out_date', '>=', $monthStart);
                })
                ->count();

            $total = (int) $stats['rooms']['totalCapacity'];

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

        $sessionBilling = [
            'name' => $sessionName,
            'totalAmount' => $totalAmount,
            'paidAmount' => $paidAmount,
            'pendingAmount' => $pendingAmount,
            'refundAmount' => 0,
            'totalBills' => $totalBills,
            'billsProcessed' => $billsProcessed,
            'collectionRate' => $totalAmount > 0
                ? round(($paidAmount / $totalAmount) * 100)
                : 0,
        ];

        $latestComplaints = Complaint::with('resident')
            ->whereIn('status', ['open', 'in_progress'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($complaint) => [
                'id' => $complaint->id,
                'category' => $complaint->category ?? $complaint->subject ?? 'Complaint',
                'residentName' => $complaint->resident
                    ? trim($complaint->resident->first_name . ' ' . $complaint->resident->last_name)
                    : '-',
                'description' => $complaint->description ?? $complaint->complaint ?? '',
                'status' => $complaint->status,
            ]);

        $latestLeaves = LeaveRequest::with('resident')
            ->where('final_status', 'pending')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($leave) => [
                'id' => $leave->id,
                'residentName' => $leave->resident
                    ? trim($leave->resident->first_name . ' ' . $leave->resident->last_name)
                    : '-',
                'reason' => $leave->reason ?? '-',
                'fromDate' => $leave->from_date ?? $leave->start_date ?? null,
                'toDate' => $leave->to_date ?? $leave->end_date ?? null,
                'status' => $leave->final_status,
            ]);

        $misReports = collect(range(0, 2))->map(function ($i) use ($today) {
            $month = $today->copy()->subMonths($i);

            return [
                'id' => $month->format('Y-m'),
                'label' => $month->format('F Y') . ' MIS Report',
                'generatedAt' => $month->copy()->endOfMonth()->toDateString(),
                'url' => '#',
            ];
        });

        $recentActivity = Resident::orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($resident) => [
                'id' => $resident->id,
                'name' => trim($resident->first_name . ' ' . $resident->last_name),
                'action' => 'joined',
                'date' => $resident->created_at,
                'type' => 'resident',
            ]);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'occupancyTrend' => $occupancyTrend,
            'recentActivity' => $recentActivity,
            'sessionBilling' => $sessionBilling,
            'latestComplaints' => $latestComplaints,
            'latestLeaves' => $latestLeaves,
            'misReports' => $misReports,
        ]);
    }
}
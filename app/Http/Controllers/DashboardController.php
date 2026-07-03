<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Building;
use App\Models\Complaint;
use App\Models\FeeInvoice;
use App\Models\LeaveRequest;
use App\Models\Resident;
use App\Models\Room;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
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

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $occupancyTrend = collect($months)->values()->map(function ($month, $i) {
            return [
                'month' => $month,
                'total' => 100 + $i * 5,
                'occupied' => 75 + $i * 4,
                'vacant' => 25 + $i,
            ];
        });

        $recentActivity = Resident::orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => trim($r->first_name.' '.$r->last_name),
                'action' => 'joined',
                'date' => $r->created_at,
                'type' => 'resident',
            ]);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'occupancyTrend' => $occupancyTrend,
            'recentActivity' => $recentActivity,
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Models\Payment;
use App\Models\Resident;
use App\Models\ResidentStay;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $type = $request->string('type')->toString() ?: 'occupancy';

        $data = match ($type) {
            'admissions' => Resident::orderByDesc('created_at')->limit(200)
                ->get(['id', 'resident_code', 'first_name', 'last_name', 'course', 'status', 'created_at']),
            'payments' => Payment::with('invoice.resident')->orderByDesc('payment_date')->limit(200)->get(),
            'outstanding' => FeeInvoice::with('resident')->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderByDesc('due_date')->limit(200)->get(),
            'checkins' => ResidentStay::with(['resident', 'room', 'building'])->where('status', 'active')
                ->orderByDesc('check_in_date')->limit(200)->get(),
            default => Resident::with('currentStay.room')->orderBy('first_name')->limit(200)->get(),
        };

        return Inertia::render('Reports/Index', [
            'type' => $type,
            'rows' => $data,
        ]);
    }
}
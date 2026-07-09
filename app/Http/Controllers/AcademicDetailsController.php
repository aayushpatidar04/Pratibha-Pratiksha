<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicDetailsController extends Controller
{
    /**
     * Dedicated Academic Details screen. Still backed by the same `residents`
     * table/columns as the main profile — this is a focused view + form for just
     * course/institute/batch/year/roll_number, edited separately from the main
     * resident record per the requirement doc.
     */
    public function index(Request $request): Response
    {
        $query = Resident::whereIn('status', ['active', 'upcoming']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%");
            });
        }
        if ($course = $request->string('course')->toString()) {
            $query->where('course', $course);
        }
        if ($batch = $request->string('batch')->toString()) {
            $query->where('batch', $batch);
        }

        $residents = $query->orderBy('first_name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($r) => $r->only([
                'id',
                'first_name',
                'last_name',
                'resident_code',
                'photo_url',
                'course',
                'institute',
                'batch',
                'year',
                'roll_number',
            ]));

        return Inertia::render('Residents/AcademicDetails/Index', [
            'residents' => $residents,
            'filters' => $request->only('search', 'course', 'batch'),
            'filterOptions' => [
                'courses' => Resident::whereNotNull('course')->distinct()->orderBy('course')->pluck('course'),
                'batches' => Resident::whereNotNull('batch')->distinct()->orderBy('batch')->pluck('batch'),
            ],
        ]);
    }

    public function update(Request $request, Resident $resident): RedirectResponse
    {
        $validated = $request->validate([
            'course' => 'nullable|string|max:100',
            'institute' => 'nullable|string|max:200',
            'batch' => 'nullable|string|max:50',
            'year' => 'nullable|integer|min:1|max:10',
            'roll_number' => 'nullable|string|max:50',
        ]);

        $resident->update($validated);

        return back()->with('success', 'Academic details updated.');
    }
}
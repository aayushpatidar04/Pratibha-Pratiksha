<?php

namespace App\Http\Controllers;

use App\Models\MessMenu;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MessMenuController extends Controller
{
    public function index(Request $request): Response
    {
        $week = $request->input('week');

        $weekStart = $week
            ? Carbon::parse($week)->startOfWeek()
            : now()->startOfWeek();

        $weekEnd = $weekStart->copy()->addDays(6);

        $this->createWeekMenuFromPreviousWeekIfEmpty($weekStart);

        $menus = MessMenu::whereBetween('menu_date', [
            $weekStart->toDateString(),
            $weekEnd->toDateString(),
        ])
            ->orderBy('menu_date')
            ->orderBy('meal_type')
            ->orderByDesc('id')
            ->get()
            ->unique(fn($menu) => $menu->menu_date->toDateString() . '-' . $menu->meal_type)
            ->values();

        return Inertia::render('Mess/Index', [
            'menus' => $menus,
            'weekStart' => $weekStart->toDateString(),
        ]);
    }

    private function createWeekMenuFromPreviousWeekIfEmpty(Carbon $weekStart): void
    {
        $weekStart = $weekStart->copy()->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        $currentWeekMenuExists = MessMenu::whereBetween('menu_date', [
            $weekStart->toDateString(),
            $weekEnd->toDateString(),
        ])
            ->exists();

        if ($currentWeekMenuExists) {
            return;
        }

        $previousWeekStart = $weekStart->copy()->subWeek()->startOfDay();
        $previousWeekEnd = $previousWeekStart->copy()->addDays(6)->endOfDay();

        $previousMenus = MessMenu::whereBetween('menu_date', [
            $previousWeekStart->toDateString(),
            $previousWeekEnd->toDateString(),
        ])
            ->orderBy('menu_date')
            ->orderBy('meal_type')
            ->orderByDesc('id')
            ->get()
            ->unique(fn($menu) => Carbon::parse($menu->menu_date)->toDateString() . '-' . $menu->meal_type)
            ->values();

        if ($previousMenus->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($previousMenus, $weekStart, $previousWeekStart) {
            foreach ($previousMenus as $menu) {
                $oldMenuDate = Carbon::parse($menu->menu_date)->startOfDay();

                $daysDifference = $previousWeekStart->diffInDays($oldMenuDate, false);

                $newMenuDate = $weekStart->copy()
                    ->addDays($daysDifference)
                    ->toDateString();

                MessMenu::updateOrCreate(
                    [
                        'menu_date' => $newMenuDate,
                        'meal_type' => $menu->meal_type,
                    ],
                    [
                        'items' => $menu->items,
                        'special_notes' => $menu->special_notes,
                    ]
                );
            }
        });
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:mess_menu,id',
            'menu_date' => 'required|date',
            'meal_type' => 'required|in:breakfast,lunch,snacks,dinner',
            'items' => 'required|string',
            'special_notes' => 'nullable|string',
        ]);

        $menuDate = Carbon::parse($validated['menu_date'])->toDateString();

        if (!empty($validated['id'])) {
            $menu = MessMenu::findOrFail($validated['id']);

            $menu->update([
                'menu_date' => $menuDate,
                'meal_type' => $validated['meal_type'],
                'items' => $validated['items'],
                'special_notes' => $validated['special_notes'] ?? null,
            ]);

            MessMenu::where('id', '!=', $menu->id)
                ->whereDate('menu_date', $menuDate)
                ->where('meal_type', $validated['meal_type'])
                ->delete();
        } else {
            $existing = MessMenu::whereDate('menu_date', $menuDate)
                ->where('meal_type', $validated['meal_type'])
                ->latest('id')
                ->first();

            if ($existing) {
                $existing->update([
                    'items' => $validated['items'],
                    'special_notes' => $validated['special_notes'] ?? null,
                ]);

                MessMenu::where('id', '!=', $existing->id)
                    ->whereDate('menu_date', $menuDate)
                    ->where('meal_type', $validated['meal_type'])
                    ->delete();
            } else {
                MessMenu::create([
                    'menu_date' => $menuDate,
                    'meal_type' => $validated['meal_type'],
                    'items' => $validated['items'],
                    'special_notes' => $validated['special_notes'] ?? null,
                ]);
            }
        }

        return back()->with('success', 'Menu saved.');
    }

    public function destroy($id): RedirectResponse
    {
        $menu = MessMenu::findOrFail($id);

        MessMenu::whereDate('menu_date', $menu->menu_date->toDateString())
            ->where('meal_type', $menu->meal_type)
            ->delete();

        return back()->with('success', 'Menu entry removed.');
    }
}
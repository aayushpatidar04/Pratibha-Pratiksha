<?php

namespace App\Http\Controllers\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\MessMenu;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MessMenuController extends Controller
{
    public function index(
        Request $request
    ): Response {
        /** @var Resident|null $resident */
        $resident =
            Auth::guard('resident')->user();

        abort_unless($resident, 401);

        $validated = $request->validate([
            'week' => [
                'nullable',
                'date',
            ],
        ]);

        $resident->loadMissing([
            'currentStay.building:id,name',
        ]);

        $currentStay =
            $resident->currentStay;

        $weekStart = filled(
            $validated['week'] ?? null
        )
            ? Carbon::parse(
                $validated['week']
            )->startOfWeek()
            : now()->startOfWeek();

        $weekEnd = $weekStart
            ->copy()
            ->addDays(6);

        $menus = MessMenu::query()
            ->whereBetween(
                'menu_date',
                [
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                ]
            )
            ->orderBy('menu_date')
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
            ->unique(
                fn(MessMenu $menu) =>
                    $menu->menu_date
                        ->toDateString()
                    . '-'
                    . $menu->meal_type
            )
            ->values()
            ->map(
                fn(MessMenu $menu) => [
                    'id' => $menu->id,

                    'menu_date' =>
                        $menu->menu_date
                            ->toDateString(),

                    'meal_type' =>
                        $menu->meal_type,

                    'items' =>
                        $menu->items,

                    'special_notes' =>
                        $menu->special_notes,
                ]
            );

        $today = now()->toDateString();

        $todayMenus = $menus
            ->where(
                'menu_date',
                $today
            )
            ->values();

        return Inertia::render(
            'ResidentPortal/Mess/Index',
            [
                'menus' => $menus,

                'todayMenus' =>
                    $todayMenus,

                'weekStart' =>
                    $weekStart
                        ->toDateString(),

                'today' => $today,

                'building' =>
                    $currentStay?->building
                    ? [
                        'id' =>
                            $currentStay
                                ->building
                                ->id,

                        'name' =>
                            $currentStay
                                ->building
                                ->name,
                    ]
                    : null,
            ]
        );
    }
}
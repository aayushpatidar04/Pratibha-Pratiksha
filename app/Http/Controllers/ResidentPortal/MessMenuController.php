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

        $buildingId =
            $currentStay?->building_id;

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
            ->where(function (
                $query
            ) use ($buildingId): void {
                if ($buildingId) {
                    $query
                        ->where(
                            'building_id',
                            $buildingId
                        )
                        ->orWhereNull(
                            'building_id'
                        );
                } else {
                    $query->whereNull(
                        'building_id'
                    );
                }
            })
            ->orderByRaw(
                'CASE WHEN building_id IS NULL THEN 2 ELSE 1 END'
            )
            ->orderBy('menu_date')
            ->orderBy('meal_type')
            ->get()
            ->unique(
                fn (MessMenu $menu) =>
                    $menu->menu_date
                        ->toDateString()
                    . '-'
                    . $menu->meal_type
            )
            ->values()
            ->map(
                fn (MessMenu $menu) => [
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
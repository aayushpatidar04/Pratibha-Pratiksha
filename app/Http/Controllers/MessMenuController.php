<?php

namespace App\Http\Controllers;

use App\Models\Building;
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
        $validated = $request->validate([
            'week' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'building_id' => [
                'nullable',
                'integer',
                'exists:buildings,id',
            ],
        ]);

        $weekStart = filled($validated['week'] ?? null)
            ? Carbon::createFromFormat(
                'Y-m-d',
                $validated['week']
            )->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart
            ->copy()
            ->addDays(6);

        $buildings = Building::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        $selectedBuildingId = isset(
            $validated['building_id']
        )
            ? (int) $validated['building_id']
            : (int) ($buildings->first()?->id ?? 0);

        if ($selectedBuildingId) {
            $this->prepareBuildingWeekMenu(
                weekStart: $weekStart,
                buildingId: $selectedBuildingId
            );
        }

        $menus = MessMenu::query()
            ->where(
                'building_id',
                $selectedBuildingId
            )
            ->whereBetween('menu_date', [
                $weekStart->toDateString(),
                $weekEnd->toDateString(),
            ])
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
            ->get();

        return Inertia::render('Mess/Index', [
            'menus' => $menus,

            'weekStart' =>
                $weekStart->toDateString(),

            'selectedBuildingId' =>
                $selectedBuildingId,

            'buildings' => $buildings,
        ]);
    }

    private function prepareBuildingWeekMenu(
        Carbon $weekStart,
        int $buildingId
    ): void {
        $weekStart = $weekStart
            ->copy()
            ->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart
            ->copy()
            ->addDays(6);

        /*
         * Do nothing when the selected building already has
         * at least one entry for this week.
         */
        $buildingWeekExists = MessMenu::query()
            ->where('building_id', $buildingId)
            ->whereBetween('menu_date', [
                $weekStart->toDateString(),
                $weekEnd->toDateString(),
            ])
            ->exists();

        if ($buildingWeekExists) {
            return;
        }

        /*
         * First preference:
         * legacy/common menu from the exact requested week.
         *
         * This handles your original records where
         * building_id was null.
         */
        $commonCurrentWeekMenus =
            $this->getWeekMenus(
                weekStart: $weekStart,
                buildingId: null
            );

        if ($commonCurrentWeekMenus->isNotEmpty()) {
            $this->copyWeekMenus(
                sourceMenus: $commonCurrentWeekMenus,
                sourceWeekStart: $weekStart,
                destinationWeekStart: $weekStart,
                destinationBuildingId: $buildingId
            );

            return;
        }

        $previousWeekStart = $weekStart
            ->copy()
            ->subWeek()
            ->startOfWeek(Carbon::MONDAY);

        /*
         * Second preference:
         * previous week menu of the selected building.
         */
        $previousBuildingMenus =
            $this->getWeekMenus(
                weekStart: $previousWeekStart,
                buildingId: $buildingId
            );

        if ($previousBuildingMenus->isNotEmpty()) {
            $this->copyWeekMenus(
                sourceMenus: $previousBuildingMenus,
                sourceWeekStart: $previousWeekStart,
                destinationWeekStart: $weekStart,
                destinationBuildingId: $buildingId
            );

            return;
        }

        /*
         * Last fallback:
         * previous week's legacy/common menu.
         */
        $previousCommonMenus =
            $this->getWeekMenus(
                weekStart: $previousWeekStart,
                buildingId: null
            );

        if ($previousCommonMenus->isNotEmpty()) {
            $this->copyWeekMenus(
                sourceMenus: $previousCommonMenus,
                sourceWeekStart: $previousWeekStart,
                destinationWeekStart: $weekStart,
                destinationBuildingId: $buildingId
            );
        }
    }

    private function getWeekMenus(
        Carbon $weekStart,
        ?int $buildingId
    ) {
        $weekStart = $weekStart
            ->copy()
            ->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart
            ->copy()
            ->addDays(6);

        return MessMenu::query()
            ->when(
                $buildingId === null,
                fn($query) =>
                $query->whereNull(
                    'building_id'
                ),
                fn($query) =>
                $query->where(
                    'building_id',
                    $buildingId
                )
            )
            ->whereBetween('menu_date', [
                $weekStart->toDateString(),
                $weekEnd->toDateString(),
            ])
            ->orderBy('menu_date')
            ->orderBy('meal_type')
            ->orderByDesc('id')
            ->get()
            ->unique(
                fn(MessMenu $menu) =>
                $menu->menu_date->toDateString()
                . '-'
                . $menu->meal_type
            )
            ->values();
    }

    private function copyWeekMenus(
        $sourceMenus,
        Carbon $sourceWeekStart,
        Carbon $destinationWeekStart,
        int $destinationBuildingId
    ): void {
        DB::transaction(
            function () use ($sourceMenus, $sourceWeekStart, $destinationWeekStart, $destinationBuildingId): void {
                foreach ($sourceMenus as $menu) {
                    $sourceDate = Carbon::parse(
                        $menu->menu_date
                    )->startOfDay();

                    $dayOffset =
                        $sourceWeekStart
                            ->copy()
                            ->startOfDay()
                            ->diffInDays(
                                $sourceDate,
                                false
                            );

                    if (
                        $dayOffset < 0 ||
                        $dayOffset > 6
                    ) {
                        continue;
                    }

                    $destinationDate =
                        $destinationWeekStart
                            ->copy()
                            ->addDays($dayOffset)
                            ->toDateString();

                    MessMenu::updateOrCreate(
                        [
                            'building_id' =>
                                $destinationBuildingId,

                            'menu_date' =>
                                $destinationDate,

                            'meal_type' =>
                                $menu->meal_type,
                        ],
                        [
                            'items' =>
                                $menu->items,

                            'special_notes' =>
                                $menu->special_notes,
                        ]
                    );
                }
            }
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'id' => [
                'nullable',
                'integer',
                'exists:mess_menu,id',
            ],

            'building_id' => [
                'required',
                'integer',
                'exists:buildings,id',
            ],

            'menu_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'meal_type' => [
                'required',
                'in:breakfast,lunch,snacks,dinner',
            ],

            'items' => [
                'required',
                'string',
                'max:3000',
            ],

            'special_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $data = [
            'building_id' =>
                (int) $validated['building_id'],

            'menu_date' =>
                Carbon::createFromFormat(
                    'Y-m-d',
                    $validated['menu_date']
                )->toDateString(),

            'meal_type' =>
                $validated['meal_type'],

            'items' =>
                trim($validated['items']),

            'special_notes' =>
                filled(
                    $validated['special_notes']
                    ?? null
                )
                ? trim(
                    $validated['special_notes']
                )
                : null,
        ];

        DB::transaction(function () use ($validated, $data): void {
            if (!empty($validated['id'])) {
                $menu = MessMenu::query()
                    ->findOrFail(
                        $validated['id']
                    );

                $menu->update($data);

                /*
                 * Remove an older duplicate if the date,
                 * meal or building was changed.
                 */
                MessMenu::query()
                    ->whereKeyNot($menu->id)
                    ->where(
                        'building_id',
                        $data['building_id']
                    )
                    ->whereDate(
                        'menu_date',
                        $data['menu_date']
                    )
                    ->where(
                        'meal_type',
                        $data['meal_type']
                    )
                    ->delete();

                return;
            }

            MessMenu::updateOrCreate(
                [
                    'building_id' =>
                        $data['building_id'],

                    'menu_date' =>
                        $data['menu_date'],

                    'meal_type' =>
                        $data['meal_type'],
                ],
                [
                    'items' =>
                        $data['items'],

                    'special_notes' =>
                        $data['special_notes'],
                ]
            );
        });

        return back()->with(
            'success',
            'Menu saved successfully.'
        );
    }

    public function destroy(
        MessMenu $menu
    ): RedirectResponse {
        $menu->delete();

        return back()->with(
            'success',
            'Menu entry removed.'
        );
    }
}
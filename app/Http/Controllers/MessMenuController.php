<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\MessItem;
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

        /*
        * If the requested week has no menu yet,
        * automatically prepare it from the previous week.
        */
        $this->prepareWeekMenu($weekStart);

        $menus = MessMenu::query()
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

        $messItems = MessItem::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return Inertia::render('Mess/Index', [
            'menus' => $menus,

            'weekStart' =>
                $weekStart->toDateString(),

            'messItems' => $messItems,
        ]);
    }

    private function prepareWeekMenu(Carbon $weekStart): void {
        $weekStart = $weekStart
            ->copy()
            ->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart
            ->copy()
            ->addDays(6);

        /*
        * If even one menu entry exists for this week,
        * do not recreate/copy the week.
        */
        $weekExists = MessMenu::query()
            ->whereBetween('menu_date', [
                $weekStart->toDateString(),
                $weekEnd->toDateString(),
            ])
            ->exists();

        if ($weekExists) {
            return;
        }

        $previousWeekStart = $weekStart
            ->copy()
            ->subWeek()
            ->startOfWeek(Carbon::MONDAY);

        $previousWeekMenus = $this->getWeekMenus(
            $previousWeekStart
        );

        if ($previousWeekMenus->isEmpty()) {
            return;
        }

        $this->copyWeekMenus(
            sourceMenus: $previousWeekMenus,
            sourceWeekStart: $previousWeekStart,
            destinationWeekStart: $weekStart
        );
    }

    private function getWeekMenus(Carbon $weekStart) {
        $weekStart = $weekStart
            ->copy()
            ->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart
            ->copy()
            ->addDays(6);

        return MessMenu::query()
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

    private function copyWeekMenus($sourceMenus, Carbon $sourceWeekStart, Carbon $destinationWeekStart): void {
        DB::transaction(
            function () use (
                $sourceMenus,
                $sourceWeekStart,
                $destinationWeekStart
            ): void {
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
                            'menu_date' => $destinationDate,

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

    public function store(Request $request): RedirectResponse {
        $validated = $request->validate([
            'id' => [
                'nullable',
                'integer',
                'exists:mess_menu,id',
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
                'array',
                'min:1',
            ],

            'items.*' => [
                'required',
                'string',
                'max:255',
            ],

            'special_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $items = collect($validated['items'])
            ->map(
                fn($item) => trim($item)
            )
            ->filter()
            ->unique()
            ->values();

        if ($items->isEmpty()) {
            return back()->withErrors([
                'items' =>
                    'Please select or add at least one item.',
            ]);
        }

        $data = [
            'menu_date' =>
                Carbon::createFromFormat(
                    'Y-m-d',
                    $validated['menu_date']
                )->toDateString(),

            'meal_type' =>
                $validated['meal_type'],

            'items' =>
                $items->all(),

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

        DB::transaction(function () use (
            $validated,
            $data,
            $items
        ): void {
            /*
            * Save newly used items in master items list.
            */
            foreach ($items as $item) {
                MessItem::firstOrCreate([
                    'name' => $item,
                ]);
            }

            /*
            * Same store method for add/edit.
            */
            if (!empty($validated['id'])) {
                $menu = MessMenu::query()
                    ->findOrFail(
                        $validated['id']
                    );

                $menu->update($data);

                /*
                * Remove duplicate if date or meal type changed.
                */
                MessMenu::query()
                    ->whereKeyNot($menu->id)
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

    public function destroy(MessMenu $messMenu): RedirectResponse {
        $messMenu->delete();

        return back()->with(
            'success',
            'Menu entry removed.'
        );
    }
}
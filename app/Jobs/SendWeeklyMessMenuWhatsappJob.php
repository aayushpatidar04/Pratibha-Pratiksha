<?php

namespace App\Jobs;

use App\Models\MessMenu;
use App\Models\Resident;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as DispatchableQueueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWeeklyMessMenuWhatsappJob implements ShouldQueue
{
    use DispatchableQueueable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;

    public function __construct(
        public string $weekStart,
        public string $weekEnd
    ) {}

    public function handle(WhatsappService $whatsapp): void
    {
        $weekStart = Carbon::parse($this->weekStart);
        $weekEnd = Carbon::parse($this->weekEnd);

        $menus = MessMenu::whereBetween('menu_date', [
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
            ->get()
            ->groupBy(fn ($menu) => Carbon::parse($menu->menu_date)->toDateString());

        if ($menus->isEmpty()) {
            return;
        }

        $message = $this->buildMessage($menus, $weekStart, $weekEnd);

        $residents = Resident::where('status', 'active')
            ->get();

        foreach ($residents as $resident) {
            $numbers = collect([
                $resident->phone ?? null,
                $resident->mobile ?? null,
                $resident->whatsapp_number ?? null,
                $resident->parent_phone ?? null,
                $resident->guardian_phone ?? null,
            ])
                ->filter()
                ->unique()
                ->values();

            foreach ($numbers as $number) {
                $whatsapp->sendText($number, $message);
            }
        }
    }

    private function buildMessage($menus, Carbon $weekStart, Carbon $weekEnd): string
    {
        $meals = ['breakfast', 'lunch', 'snacks', 'dinner'];

        $message = "🍽️ *Weekly Mess Menu*\n";
        $message .= $weekStart->format('d M Y') . " to " . $weekEnd->format('d M Y') . "\n\n";

        for ($date = $weekStart->copy(); $date->lte($weekEnd); $date->addDay()) {
            $dateKey = $date->toDateString();

            $message .= "📅 *" . $date->format('l, d M') . "*\n";

            foreach ($meals as $meal) {
                $menu = $menus->get($dateKey)?->firstWhere('meal_type', $meal);

                $items = $menu?->items ?: '-';

                $message .= ucfirst($meal) . ": " . $items . "\n";

                if (!empty($menu?->special_notes)) {
                    $message .= "Note: " . $menu->special_notes . "\n";
                }
            }

            $message .= "\n";
        }

        $message .= "Regards,\nHostel Management";

        return $message;
    }
}
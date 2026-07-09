<?php

namespace App\Console\Commands;

use App\Jobs\SendWeeklyMessMenuWhatsappJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendWeeklyMessMenuWhatsapp extends Command
{
    protected $signature = 'mess:send-weekly-whatsapp';

    protected $description = 'Send upcoming week mess menu to parents and residents via WhatsApp';

    public function handle(): int
    {
        $nextWeekStart = now()->copy()->addWeek()->startOfWeek();
        $nextWeekEnd = $nextWeekStart->copy()->addDays(6);

        SendWeeklyMessMenuWhatsappJob::dispatch(
            $nextWeekStart->toDateString(),
            $nextWeekEnd->toDateString()
        );

        $this->info("Weekly mess menu WhatsApp job dispatched for {$nextWeekStart->toDateString()} to {$nextWeekEnd->toDateString()}");

        return self::SUCCESS;
    }
}
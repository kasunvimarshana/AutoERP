<?php

namespace App\Console;

use App\Console\Commands\InventoryEventConsumer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        InventoryEventConsumer::class,
    ];

    protected function schedule(Schedule $schedule): void {}

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}

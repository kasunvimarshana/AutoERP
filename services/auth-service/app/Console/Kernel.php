<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Prune expired Passport tokens daily at 1am UTC
        $schedule->command('passport:purge')->dailyAt('01:00');

        // Warm up tenant runtime config cache hourly
        $schedule->call(function () {
            app(\App\Services\RuntimeConfigService::class)->warmUpAll();
        })->hourly()->name('warm-tenant-config');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}

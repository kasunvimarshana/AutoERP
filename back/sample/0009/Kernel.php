<?php

// ════════════════════════════════════════════════════════════════════
// app/Console/Kernel.php  (schedule block only — merge into yours)
// ════════════════════════════════════════════════════════════════════

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\{
    ScanExpiryCommand,
    ProcessReordersCommand,
    GenerateSnapshotCommand,
    ClassifyAbcCommand,
    ExpireSoftReservationsCommand,
};

class Kernel extends ConsoleKernel
{
    protected $commands = [
        ScanExpiryCommand::class,
        ProcessReordersCommand::class,
        GenerateSnapshotCommand::class,
        ClassifyAbcCommand::class,
        ExpireSoftReservationsCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Expire soft reservations every 15 minutes
        $schedule->command('inventory:expire-reservations')
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        // Reorder rule evaluation — hourly
        $schedule->command('inventory:process-reorders')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Expiry scan — daily at 06:00
        $schedule->command('inventory:scan-expiry')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Monthly valuation snapshot — 1st of month at 00:05
        $schedule->command('inventory:snapshot --type=monthly')
            ->monthlyOn(1, '00:05')
            ->withoutOverlapping()
            ->runInBackground();

        // Daily snapshot (optional — enable for daily P&L)
        // $schedule->command('inventory:snapshot --type=daily')
        //     ->dailyAt('23:55')
        //     ->withoutOverlapping();

        // ABC/XYZ classification — monthly on 2nd at 01:00
        $schedule->command('inventory:classify-abc')
            ->monthlyOn(2, '01:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}

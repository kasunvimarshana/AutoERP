<?php

use App\Console\Commands\ProcessOutboxCommand;
use App\Console\Commands\RecoverSagaCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ── Outbox processor — runs every minute to publish pending domain events ──
Schedule::command(ProcessOutboxCommand::class, ['--once', '--batch=100'])
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/outbox.log'));

// ── Saga recovery monitor — warns about stuck sagas every 15 minutes ──────
Schedule::command(RecoverSagaCommand::class, ['--stuck=30'])
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/saga-recovery.log'));

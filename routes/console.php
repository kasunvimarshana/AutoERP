<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tenant:domains:revalidate')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('tenant:expire')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('tenant:storage:cleanup')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('tenant:events:publish')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('auth:retention:purge')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('vehicle-rental:finance-installments:refresh-due')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

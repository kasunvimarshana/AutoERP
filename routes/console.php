<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tenant:domains:revalidate')
    ->hourly()
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

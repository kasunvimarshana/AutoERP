<?php

protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        $schedules = CycleCountSchedule::where('status', 'active')
            ->where('next_run_at', '<=', now())
            ->get();
        foreach ($schedules as $schedule) {
            app(CycleCountService::class)->generateCountItems($schedule->id);
        }
    })->daily();
}
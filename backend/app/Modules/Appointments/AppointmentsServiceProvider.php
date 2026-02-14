<?php

namespace App\Modules\Appointments;

use Illuminate\Support\ServiceProvider;

class AppointmentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services
        $this->app->singleton(
            \App\Modules\Appointments\Services\AppointmentService::class,
            function ($app) {
                return new \App\Modules\Appointments\Services\AppointmentService();
            }
        );

        $this->app->singleton(
            \App\Modules\Appointments\Services\ServiceBayService::class,
            function ($app) {
                return new \App\Modules\Appointments\Services\ServiceBayService();
            }
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}

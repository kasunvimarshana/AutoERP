<?php

namespace App\Modules\CRM;

use Illuminate\Support\ServiceProvider;

class CRMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            \App\Modules\CRM\Repositories\CustomerRepository::class,
            function ($app) {
                return new \App\Modules\CRM\Repositories\CustomerRepository(
                    new \App\Modules\CRM\Models\Customer()
                );
            }
        );

        $this->app->bind(
            \App\Modules\CRM\Repositories\ContactRepository::class,
            function ($app) {
                return new \App\Modules\CRM\Repositories\ContactRepository(
                    new \App\Modules\CRM\Models\Contact()
                );
            }
        );

        $this->app->bind(
            \App\Modules\CRM\Repositories\VehicleRepository::class,
            function ($app) {
                return new \App\Modules\CRM\Repositories\VehicleRepository(
                    new \App\Modules\CRM\Models\Vehicle()
                );
            }
        );

        // Register services
        $this->app->singleton(
            \App\Modules\CRM\Services\CustomerService::class,
            function ($app) {
                return new \App\Modules\CRM\Services\CustomerService(
                    $app->make(\App\Modules\CRM\Repositories\CustomerRepository::class)
                );
            }
        );

        $this->app->singleton(
            \App\Modules\CRM\Services\ContactService::class,
            function ($app) {
                return new \App\Modules\CRM\Services\ContactService(
                    $app->make(\App\Modules\CRM\Repositories\ContactRepository::class)
                );
            }
        );

        $this->app->singleton(
            \App\Modules\CRM\Services\VehicleService::class,
            function ($app) {
                return new \App\Modules\CRM\Services\VehicleService(
                    $app->make(\App\Modules\CRM\Repositories\VehicleRepository::class)
                );
            }
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}

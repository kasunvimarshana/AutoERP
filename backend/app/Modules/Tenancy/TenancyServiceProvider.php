<?php

namespace App\Modules\Tenancy;

use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            \App\Modules\Tenancy\Repositories\TenantRepository::class,
            function ($app) {
                return new \App\Modules\Tenancy\Repositories\TenantRepository(
                    new \App\Modules\Tenancy\Models\Tenant()
                );
            }
        );

        // Register services
        $this->app->singleton(
            \App\Modules\Tenancy\Services\TenantService::class,
            function ($app) {
                return new \App\Modules\Tenancy\Services\TenantService(
                    $app->make(\App\Modules\Tenancy\Repositories\TenantRepository::class)
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

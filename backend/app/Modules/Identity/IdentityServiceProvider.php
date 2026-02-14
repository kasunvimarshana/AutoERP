<?php

namespace App\Modules\Identity;

use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            \App\Modules\Identity\Repositories\UserRepository::class,
            function ($app) {
                return new \App\Modules\Identity\Repositories\UserRepository(
                    new \App\Modules\Identity\Models\User()
                );
            }
        );

        // Register services
        $this->app->singleton(
            \App\Modules\Identity\Services\UserService::class,
            function ($app) {
                return new \App\Modules\Identity\Services\UserService(
                    $app->make(\App\Modules\Identity\Repositories\UserRepository::class)
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

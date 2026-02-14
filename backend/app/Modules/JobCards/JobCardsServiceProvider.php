<?php

namespace App\Modules\JobCards;

use Illuminate\Support\ServiceProvider;

class JobCardsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->bind(
            \App\Modules\JobCards\Repositories\JobCardRepository::class,
            function ($app) {
                return new \App\Modules\JobCards\Repositories\JobCardRepository(
                    new \App\Modules\JobCards\Models\JobCard()
                );
            }
        );

        $this->app->bind(
            \App\Modules\JobCards\Repositories\JobCardTaskRepository::class,
            function ($app) {
                return new \App\Modules\JobCards\Repositories\JobCardTaskRepository(
                    new \App\Modules\JobCards\Models\JobCardTask()
                );
            }
        );

        // Register services
        $this->app->singleton(
            \App\Modules\JobCards\Services\JobCardService::class,
            function ($app) {
                return new \App\Modules\JobCards\Services\JobCardService(
                    $app->make(\App\Modules\JobCards\Repositories\JobCardRepository::class),
                    $app->make(\App\Modules\JobCards\Repositories\JobCardTaskRepository::class)
                );
            }
        );

        $this->app->singleton(
            \App\Modules\JobCards\Services\JobCardTaskService::class,
            function ($app) {
                return new \App\Modules\JobCards\Services\JobCardTaskService(
                    $app->make(\App\Modules\JobCards\Repositories\JobCardTaskRepository::class)
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

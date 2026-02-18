<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Module Registry as singleton
        $this->app->singleton(\App\Services\ModuleRegistry::class, function ($app) {
            return new \App\Services\ModuleRegistry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

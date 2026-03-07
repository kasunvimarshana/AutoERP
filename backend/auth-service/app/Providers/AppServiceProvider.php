<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\MessageBrokerService::class);
    }

    public function boot(): void
    {
        // Initialize current_tenant_id
        $this->app->instance('current_tenant_id', null);
    }
}

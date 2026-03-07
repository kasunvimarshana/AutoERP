<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\MessageBrokerService::class);
        $this->app->singleton(\App\Services\PassportTokenValidationService::class);
        $this->app->singleton(\App\Services\InventoryServiceClient::class);
    }

    public function boot(): void
    {
        $this->app->instance('current_tenant_id', null);
    }
}

<?php

namespace App\Providers;

use App\Repositories\Interfaces\TenantRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use App\Webhooks\WebhookDispatcher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);

        // Webhook dispatcher as singleton to avoid repeated instantiation
        $this->app->singleton(WebhookDispatcher::class);
    }

    public function boot(): void
    {
        //
    }
}

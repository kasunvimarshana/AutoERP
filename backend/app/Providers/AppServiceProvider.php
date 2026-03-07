<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use App\Infrastructure\MessageBroker\MessageBrokerManager;
use App\Infrastructure\MultiTenant\TenantManager;
use App\Infrastructure\MultiTenant\TenantResolver;
use App\Infrastructure\Webhook\Contracts\WebhookServiceInterface;
use App\Infrastructure\Webhook\WebhookService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

/**
 * Application service provider — binds infrastructure services.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Tenant management singletons.
        $this->app->singleton(TenantManager::class);
        $this->app->alias(TenantManager::class, 'tenant.manager');

        $this->app->singleton(TenantResolver::class, function ($app) {
            return new TenantResolver($app->make(TenantManager::class));
        });

        // Message broker manager (driver-based).
        $this->app->singleton(MessageBrokerManager::class, function ($app) {
            return new MessageBrokerManager($app);
        });

        $this->app->bind(MessageBrokerInterface::class, MessageBrokerManager::class);

        // Webhook service.
        $this->app->singleton(WebhookServiceInterface::class, function ($app) {
            return new WebhookService(
                new Client(['timeout' => 30]),
                new \App\Models\Webhook()
            );
        });

        $this->app->alias(WebhookServiceInterface::class, WebhookService::class);
    }

    public function boot(): void
    {
        // Enforce HTTPS in production.
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}

<?php

namespace Modules\Integration\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Integration\Domain\Contracts\ApiKeyRepositoryInterface;
use Modules\Integration\Domain\Contracts\WebhookRepositoryInterface;
use Modules\Integration\Infrastructure\Repositories\ApiKeyRepository;
use Modules\Integration\Infrastructure\Repositories\WebhookRepository;

class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WebhookRepositoryInterface::class, WebhookRepository::class);
        $this->app->bind(ApiKeyRepositoryInterface::class, ApiKeyRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'integration');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}

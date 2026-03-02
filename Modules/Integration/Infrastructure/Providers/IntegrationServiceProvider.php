<?php

declare(strict_types=1);

namespace Modules\Integration\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Integration\Domain\Contracts\IntegrationRepositoryContract;
use Modules\Integration\Infrastructure\Repositories\IntegrationRepository;

/**
 * Integration module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            IntegrationRepositoryContract::class,
            IntegrationRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../Database/Migrations'
        );

        $this->loadRoutesFrom(
            __DIR__.'/../../routes/api.php'
        );
    }
}

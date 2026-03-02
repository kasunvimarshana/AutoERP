<?php

declare(strict_types=1);

namespace Modules\Metadata\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Metadata\Domain\Contracts\CustomFieldRepositoryContract;
use Modules\Metadata\Domain\Contracts\FeatureFlagRepositoryContract;
use Modules\Metadata\Infrastructure\Repositories\CustomFieldRepository;
use Modules\Metadata\Infrastructure\Repositories\FeatureFlagRepository;

/**
 * Metadata module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class MetadataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CustomFieldRepositoryContract::class,
            CustomFieldRepository::class,
        );

        $this->app->bind(
            FeatureFlagRepositoryContract::class,
            FeatureFlagRepository::class,
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

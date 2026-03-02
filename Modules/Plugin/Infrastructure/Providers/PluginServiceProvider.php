<?php

declare(strict_types=1);

namespace Modules\Plugin\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Plugin\Domain\Contracts\PluginRepositoryContract;
use Modules\Plugin\Infrastructure\Repositories\PluginRepository;

/**
 * Plugin module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PluginRepositoryContract::class,
            PluginRepository::class,
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

<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sales\Domain\Contracts\SalesRepositoryContract;
use Modules\Sales\Infrastructure\Repositories\SalesRepository;

/**
 * Sales module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SalesRepositoryContract::class,
            SalesRepository::class,
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

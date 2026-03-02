<?php

declare(strict_types=1);

namespace Modules\POS\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\POS\Domain\Contracts\POSRepositoryContract;
use Modules\POS\Infrastructure\Repositories\POSRepository;

/**
 * POS module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class POSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            POSRepositoryContract::class,
            POSRepository::class,
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

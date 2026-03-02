<?php

declare(strict_types=1);

namespace Modules\Procurement\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Procurement\Domain\Contracts\ProcurementRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorBillRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorRepositoryContract;
use Modules\Procurement\Infrastructure\Repositories\ProcurementRepository;
use Modules\Procurement\Infrastructure\Repositories\VendorBillRepository;
use Modules\Procurement\Infrastructure\Repositories\VendorRepository;

/**
 * Procurement module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class ProcurementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProcurementRepositoryContract::class,
            ProcurementRepository::class,
        );

        $this->app->bind(
            VendorRepositoryContract::class,
            VendorRepository::class,
        );

        $this->app->bind(
            VendorBillRepositoryContract::class,
            VendorBillRepository::class,
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

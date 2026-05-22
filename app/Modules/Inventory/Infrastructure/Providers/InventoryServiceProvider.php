<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Domain\Contracts\AccountingIntegrationContract;
use Modules\Inventory\Domain\Contracts\InventoryReadRepositoryContract;
use Modules\Inventory\Domain\Contracts\InventoryWriteRepositoryContract;
use Modules\Inventory\Domain\Contracts\UomConversionServiceContract;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryReadRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryWriteRepository;
use Modules\Inventory\Infrastructure\Services\Accounting\NullAccountingIntegration;
use Modules\Inventory\Infrastructure\Services\EloquentUomConversionService;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../Infrastructure/Config/inventory_engine.php', 'inventory-engine');

        $this->app->bind(InventoryReadRepositoryContract::class, EloquentInventoryReadRepository::class);
        $this->app->bind(InventoryWriteRepositoryContract::class, EloquentInventoryWriteRepository::class);
        $this->app->bind(UomConversionServiceContract::class, EloquentUomConversionService::class);
        $this->app->bind(AccountingIntegrationContract::class, NullAccountingIntegration::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');

        $this->publishes([
            __DIR__ . '/../../Infrastructure/Config/inventory_engine.php' => config_path('inventory-engine.php'),
        ], 'inventory-config');
    }
}

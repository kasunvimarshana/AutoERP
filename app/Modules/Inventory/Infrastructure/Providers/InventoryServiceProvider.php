<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Services\InventoryTransactionService;
use Modules\Inventory\Application\Services\InventoryValuationService;
use Modules\Inventory\Application\Services\StockAdjustmentService;
use Modules\Inventory\Application\Services\StockAvailabilityService;
use Modules\Inventory\Application\Services\StockIssuingService;
use Modules\Inventory\Application\Services\StockReceivingService;
use Modules\Inventory\Application\Services\StockReservationService;
use Modules\Inventory\Application\Services\StockTransferService;
use Modules\Inventory\Application\Support\InventoryServiceSupport;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/inventory.php', 'inventory');
        $this->app->singleton(InventoryServiceSupport::class);
        $this->app->singleton(StockAvailabilityService::class);
        $this->app->singleton(InventoryTransactionService::class);
        $this->app->singleton(InventoryValuationService::class);
        $this->app->singleton(StockReceivingService::class);
        $this->app->singleton(StockIssuingService::class);
        $this->app->singleton(StockTransferService::class);
        $this->app->singleton(StockAdjustmentService::class);
        $this->app->singleton(StockReservationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}

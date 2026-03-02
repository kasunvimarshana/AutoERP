<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Application\Services\LotService;
use Modules\Inventory\Application\Services\ReorderRuleService;
use Modules\Inventory\Application\Services\WarehouseService;
use Modules\Inventory\Domain\Contracts\LotRepositoryInterface;
use Modules\Inventory\Domain\Contracts\ReorderRuleRepositoryInterface;
use Modules\Inventory\Domain\Contracts\StockLedgerRepositoryInterface;
use Modules\Inventory\Domain\Contracts\WarehouseRepositoryInterface;
use Modules\Inventory\Infrastructure\Repositories\LotRepository;
use Modules\Inventory\Infrastructure\Repositories\ReorderRuleRepository;
use Modules\Inventory\Infrastructure\Repositories\StockLedgerRepository;
use Modules\Inventory\Infrastructure\Repositories\WarehouseRepository;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
        $this->app->bind(StockLedgerRepositoryInterface::class, StockLedgerRepository::class);
        $this->app->bind(LotRepositoryInterface::class, LotRepository::class);
        $this->app->bind(ReorderRuleRepositoryInterface::class, ReorderRuleRepository::class);

        $this->app->singleton(InventoryService::class);
        $this->app->singleton(WarehouseService::class);
        $this->app->singleton(LotService::class);
        $this->app->singleton(ReorderRuleService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}

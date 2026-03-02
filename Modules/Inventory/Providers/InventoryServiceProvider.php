<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Handlers\AdjustStockHandler;
use Modules\Inventory\Application\Handlers\TransferStockHandler;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
use Modules\Inventory\Infrastructure\Repositories\InventoryRepository;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            InventoryRepositoryInterface::class,
            InventoryRepository::class
        );

        $this->app->singleton(AdjustStockHandler::class);
        $this->app->singleton(TransferStockHandler::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
    }
}

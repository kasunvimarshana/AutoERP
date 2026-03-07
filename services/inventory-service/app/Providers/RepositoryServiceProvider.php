<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\HandleProductCreated;
use App\Listeners\HandleProductDeleted;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Repositories\InventoryRepository;
use App\Repositories\InventoryTransactionRepository;
use App\Repositories\Interfaces\InventoryRepositoryInterface;
use App\Repositories\Interfaces\InventoryTransactionRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            InventoryRepositoryInterface::class,
            fn (): InventoryRepository => new InventoryRepository(new Inventory())
        );

        $this->app->bind(
            InventoryTransactionRepositoryInterface::class,
            fn (): InventoryTransactionRepository => new InventoryTransactionRepository(new InventoryTransaction())
        );

        // Bind listeners so the IoC container can resolve them with their dependencies
        $this->app->bind(HandleProductCreated::class, function ($app): HandleProductCreated {
            return new HandleProductCreated(
                $app->make(InventoryRepositoryInterface::class)
            );
        });

        $this->app->bind(HandleProductDeleted::class, function ($app): HandleProductDeleted {
            return new HandleProductDeleted(
                $app->make(InventoryRepositoryInterface::class)
            );
        });
    }

    public function boot(): void {}
}

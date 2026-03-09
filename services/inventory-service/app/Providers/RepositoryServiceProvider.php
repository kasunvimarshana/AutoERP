<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Contracts\InventoryServiceInterface;
use App\Domain\Contracts\ProductRepositoryInterface;
use App\Infrastructure\Database\Repositories\ProductRepository;
use App\Infrastructure\Messaging\MessageBrokerFactory;
use App\Infrastructure\Services\InventoryService;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Binds interfaces to their concrete implementations.
 * Swap implementations here without touching consuming code.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );

        // Service bindings
        $this->app->singleton(
            MessageBrokerFactory::class,
            MessageBrokerFactory::class
        );

        $this->app->bind(
            InventoryServiceInterface::class,
            InventoryService::class
        );
    }
}

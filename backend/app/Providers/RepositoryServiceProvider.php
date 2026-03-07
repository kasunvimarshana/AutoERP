<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auth\Contracts\AuthRepositoryInterface;
use App\Domain\Inventory\Contracts\InventoryRepositoryInterface;
use App\Domain\Order\Contracts\OrderRepositoryInterface;
use App\Domain\Tenant\Contracts\TenantRepositoryInterface;
use App\Infrastructure\Repositories\Inventory\ProductRepository;
use App\Infrastructure\Repositories\Order\OrderRepository;
use App\Infrastructure\Repositories\Tenant\TenantRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository service provider — binds interfaces to concrete implementations.
 *
 * To swap a repository implementation, update the binding here only.
 * All other code depends on the interface.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            InventoryRepositoryInterface::class,
            ProductRepository::class
        );

        $this->app->bind(
            OrderRepositoryInterface::class,
            OrderRepository::class
        );

        $this->app->bind(
            TenantRepositoryInterface::class,
            TenantRepository::class
        );

        $this->app->bind(
            AuthRepositoryInterface::class,
            \App\Infrastructure\Repositories\Auth\UserRepository::class
        );
    }
}

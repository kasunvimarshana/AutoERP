<?php

namespace App\Infrastructure\Providers;

use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\OrderModel;
use App\Infrastructure\Persistence\Repositories\EloquentOrderRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * OrderInfrastructureServiceProvider
 *
 * Binds Domain interfaces to Infrastructure implementations.
 * Register this provider in bootstrap/providers.php (Laravel 11+)
 * or config/app.php 'providers' array (Laravel 10).
 */
class OrderInfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the Domain repository interface to the Eloquent implementation.
        // All code that depends on OrderRepositoryInterface will receive
        // EloquentOrderRepository automatically via the container.
        $this->app->bind(
            OrderRepositoryInterface::class,
            fn () => new EloquentOrderRepository(new OrderModel())
        );
    }

    public function boot(): void
    {
        // Load context-specific migrations
        $this->loadMigrationsFrom(
            __DIR__ . '/../Persistence/Migrations'
        );

        // Register domain event listeners
        // Event::listen(
        //     \App\Domain\Order\Events\OrderWasPlaced::class,
        //     \App\Infrastructure\Events\SendOrderConfirmationEmail::class,
        // );
        //
        // Event::listen(
        //     \App\Domain\Order\Events\OrderWasCancelled::class,
        //     \App\Infrastructure\Events\NotifyCustomerOfCancellation::class,
        // );
    }
}

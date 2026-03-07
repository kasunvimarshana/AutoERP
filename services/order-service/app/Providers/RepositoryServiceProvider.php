<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\HandleInventoryRelease;
use App\Listeners\HandleInventoryReservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use App\Services\OrderSagaService;
use App\Services\OrderService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrderRepositoryInterface::class,
            fn (): OrderRepository => new OrderRepository(new Order())
        );

        $this->app->bind(
            OrderItemRepositoryInterface::class,
            fn (): OrderItemRepository => new OrderItemRepository(new OrderItem())
        );

        $this->app->bind(OrderSagaService::class, function ($app): OrderSagaService {
            return new OrderSagaService(
                $app->make(OrderRepositoryInterface::class),
                $app->make(OrderItemRepositoryInterface::class),
            );
        });

        $this->app->bind(OrderService::class, function ($app): OrderService {
            return new OrderService(
                $app->make(OrderRepositoryInterface::class),
                $app->make(OrderSagaService::class),
            );
        });

        $this->app->bind(HandleInventoryReservation::class, function ($app): HandleInventoryReservation {
            return new HandleInventoryReservation(
                $app->make(OrderRepositoryInterface::class)
            );
        });

        $this->app->bind(HandleInventoryRelease::class, function ($app): HandleInventoryRelease {
            return new HandleInventoryRelease(
                $app->make(OrderRepositoryInterface::class)
            );
        });
    }

    public function boot(): void {}
}

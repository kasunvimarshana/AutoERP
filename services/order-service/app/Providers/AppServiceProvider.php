<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Order\Services\OrderService;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Messaging\EventPublisher;
use App\Infrastructure\Persistence\Models\Order;
use App\Infrastructure\Persistence\Repositories\OrderRepository;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider — Order Service DI bindings.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // EventPublisher
        $this->app->singleton(EventPublisher::class, function ($app) {
            return new EventPublisher(
                host:     (string) config('rabbitmq.host',     'rabbitmq'),
                port:     (int)    config('rabbitmq.port',     5672),
                user:     (string) config('rabbitmq.user',     'guest'),
                password: (string) config('rabbitmq.password', 'guest'),
                vhost:    (string) config('rabbitmq.vhost',    'kvsaas'),
            );
        });

        // Repository
        $this->app->bind(OrderRepositoryInterface::class, function ($app) {
            return new OrderRepository($app->make(Order::class));
        });

        // OrderService
        $this->app->singleton(OrderService::class, function ($app) {
            return new OrderService(
                $app->make(OrderRepositoryInterface::class),
                $app->make(EventPublisher::class),
                inventoryServiceUrl: (string) config('services.inventory_service.url', 'http://inventory-service:8004'),
            );
        });
    }

    public function boot(): void {}
}

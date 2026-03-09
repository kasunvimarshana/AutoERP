<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Contracts\Repositories\InventoryRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\StockMovementRepositoryInterface;
use App\Contracts\Services\InventoryServiceInterface;
use App\Contracts\Services\StockServiceInterface;
use App\Infrastructure\Messaging\Brokers\KafkaMessageBroker;
use App\Infrastructure\Messaging\Brokers\RabbitMQMessageBroker;
use App\Infrastructure\Repositories\InventoryRepository;
use App\Infrastructure\Repositories\ProductRepository;
use App\Infrastructure\Repositories\StockMovementRepository;
use App\Services\InventoryService;
use App\Services\StockService;
use Illuminate\Support\ServiceProvider;

/**
 * Application Service Provider for Inventory Service
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
        $this->app->bind(StockMovementRepositoryInterface::class, StockMovementRepository::class);

        // Service bindings
        $this->app->bind(InventoryServiceInterface::class, InventoryService::class);
        $this->app->bind(StockServiceInterface::class, StockService::class);

        // Message Broker - pluggable
        $this->app->bind(MessageBrokerInterface::class, function ($app) {
            $driver = config('messaging.driver', 'rabbitmq');
            return match ($driver) {
                'kafka' => $app->make(KafkaMessageBroker::class),
                default => $app->make(RabbitMQMessageBroker::class),
            };
        });

        // Bind RabbitMQ broker with config
        $this->app->when(RabbitMQMessageBroker::class)
            ->needs('$host')->give(fn () => config('messaging.rabbitmq.host'))
            ->needs('$port')->give(fn () => config('messaging.rabbitmq.port'))
            ->needs('$user')->give(fn () => config('messaging.rabbitmq.user'))
            ->needs('$password')->give(fn () => config('messaging.rabbitmq.password'))
            ->needs('$vhost')->give(fn () => config('messaging.rabbitmq.vhost'));

        $this->app->when(KafkaMessageBroker::class)
            ->needs('$brokers')->give(fn () => config('messaging.kafka.brokers'));
    }

    public function boot(): void {}
}

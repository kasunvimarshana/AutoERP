<?php

namespace App\Providers;

use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Inventory\Repositories\InventoryRepositoryInterface;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Order\Repositories\OrderRepositoryInterface;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Repositories\ProductRepositoryInterface;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Repositories\UserRepositoryInterface;
use App\Services\MessageBroker\MessageBrokerInterface;
use App\Services\MessageBroker\RabbitMQService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);

        $this->app->singleton(MessageBrokerInterface::class, function () {
            $config = config('queue.connections.rabbitmq');
            return new RabbitMQService(
                host: $config['host'],
                port: (int) $config['port'],
                user: $config['login'],
                password: $config['password'],
                vhost: $config['vhost'],
            );
        });
    }

    public function boot(): void
    {
        //
    }
}

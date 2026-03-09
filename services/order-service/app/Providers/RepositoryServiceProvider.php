<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Contracts\OrderRepositoryInterface;
use App\Domain\Contracts\OrderServiceInterface;
use App\Infrastructure\Database\Repositories\OrderRepository;
use App\Infrastructure\Messaging\MessageBrokerFactory;
use App\Infrastructure\Services\OrderService;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider - Order Service
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);

        $this->app->singleton(MessageBrokerFactory::class, MessageBrokerFactory::class);

        $this->app->bind(OrderServiceInterface::class, OrderService::class);
    }
}

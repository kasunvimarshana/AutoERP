<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\TenantServiceInterface;
use App\Infrastructure\Messaging\Brokers\KafkaMessageBroker;
use App\Infrastructure\Messaging\Brokers\RabbitMQMessageBroker;
use App\Infrastructure\Repositories\TenantRepository;
use App\Infrastructure\Repositories\UserRepository;
use App\Services\TenantService;
use Illuminate\Support\ServiceProvider;

/**
 * Application Service Provider
 *
 * Registers all service bindings, repository bindings,
 * and infrastructure implementations.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Service bindings
        $this->app->bind(TenantServiceInterface::class, TenantService::class);

        // Message Broker - pluggable via config
        $this->app->bind(MessageBrokerInterface::class, function ($app) {
            $driver = config('messaging.driver', 'rabbitmq');

            return match ($driver) {
                'kafka' => $app->make(KafkaMessageBroker::class),
                default => $app->make(RabbitMQMessageBroker::class),
            };
        });
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        //
    }
}

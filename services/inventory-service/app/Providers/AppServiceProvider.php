<?php

namespace App\Providers;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use App\MessageBroker\KafkaBroker;
use App\MessageBroker\NullBroker;
use App\MessageBroker\RabbitMQBroker;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use App\Repositories\InventoryRepository;
use App\Services\CrossServiceInventoryService;
use App\Webhooks\WebhookDispatcher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);

        // Cross-service helper (singleton – stateless HTTP client)
        $this->app->singleton(CrossServiceInventoryService::class);

        // Webhook dispatcher (singleton)
        $this->app->singleton(WebhookDispatcher::class);

        // Message broker – resolved at runtime based on config
        $this->app->singleton(MessageBrokerInterface::class, function () {
            return match (config('services.message_broker', 'null')) {
                'rabbitmq' => new RabbitMQBroker(
                    host:     config('services.rabbitmq.host',     'rabbitmq'),
                    port:     (int) config('services.rabbitmq.port', 5672),
                    user:     config('services.rabbitmq.user',     'guest'),
                    password: config('services.rabbitmq.password', 'guest'),
                    vhost:    config('services.rabbitmq.vhost',    '/'),
                    exchange: config('services.rabbitmq.exchange', 'saas.events'),
                ),
                'kafka' => new KafkaBroker(
                    brokers: config('services.kafka.brokers',  'kafka:9092'),
                    groupId: config('services.kafka.group_id', 'inventory-service'),
                ),
                default => new NullBroker(),
            };
        });
    }

    public function boot(): void
    {
        // Force HTTPS in production
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}

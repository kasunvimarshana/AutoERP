<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Contracts\Repositories\SagaRepositoryInterface;
use App\Contracts\Saga\SagaOrchestratorInterface;
use App\Domain\Saga\Definitions\CreateOrderSaga;
use App\Infrastructure\Messaging\Brokers\KafkaMessageBroker;
use App\Infrastructure\Messaging\Brokers\RabbitMQMessageBroker;
use App\Infrastructure\Repositories\SagaRepository;
use App\Services\SagaOrchestrator;
use Illuminate\Support\ServiceProvider;

/**
 * App Service Provider for Saga Orchestrator
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(SagaRepositoryInterface::class, SagaRepository::class);

        // Message Broker - pluggable
        $this->app->bind(MessageBrokerInterface::class, function ($app) {
            $driver = config('messaging.driver', 'rabbitmq');
            return match ($driver) {
                'kafka' => $app->make(KafkaMessageBroker::class),
                default => $app->make(RabbitMQMessageBroker::class),
            };
        });

        $this->app->when(RabbitMQMessageBroker::class)
            ->needs('$host')->give(fn () => config('messaging.rabbitmq.host'))
            ->needs('$port')->give(fn () => config('messaging.rabbitmq.port'))
            ->needs('$user')->give(fn () => config('messaging.rabbitmq.user'))
            ->needs('$password')->give(fn () => config('messaging.rabbitmq.password'))
            ->needs('$vhost')->give(fn () => config('messaging.rabbitmq.vhost'));

        $this->app->when(KafkaMessageBroker::class)
            ->needs('$brokers')->give(fn () => config('messaging.kafka.brokers'));

        // Saga Orchestrator - singleton to maintain registered definitions
        $this->app->singleton(SagaOrchestratorInterface::class, function ($app) {
            $orchestrator = new SagaOrchestrator(
                $app->make(SagaRepositoryInterface::class),
                $app->make(MessageBrokerInterface::class),
                $app->make(\Psr\Log\LoggerInterface::class)
            );

            // Register all saga definitions
            $orchestrator->registerDefinition(new CreateOrderSaga());

            return $orchestrator;
        });
    }

    public function boot(): void {}
}

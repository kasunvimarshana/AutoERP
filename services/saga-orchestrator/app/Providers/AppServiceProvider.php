<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Contracts\SagaOrchestratorInterface;
use App\Infrastructure\Messaging\Brokers\KafkaBroker;
use App\Infrastructure\Messaging\Brokers\LogBroker;
use App\Infrastructure\Messaging\Brokers\RabbitMQBroker;
use App\Infrastructure\Messaging\MessageBrokerFactory;
use App\Infrastructure\Services\SagaOrchestrator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RabbitMQBroker::class, function ($app) {
            $config = config('messaging.rabbitmq');
            return new RabbitMQBroker(
                $config['host'], $config['port'],
                $config['user'], $config['password'], $config['vhost']
            );
        });
        $this->app->bind(KafkaBroker::class, fn ($app) => new KafkaBroker(config('messaging.kafka.brokers')));
        $this->app->bind(LogBroker::class, fn () => new LogBroker());
        $this->app->singleton(MessageBrokerFactory::class, MessageBrokerFactory::class);
        $this->app->bind(SagaOrchestratorInterface::class, SagaOrchestrator::class);
    }

    public function boot(): void {}
}

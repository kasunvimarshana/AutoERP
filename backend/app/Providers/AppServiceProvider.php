<?php
namespace App\Providers;

use App\Infrastructure\MessageBroker\KafkaBroker;
use App\Infrastructure\MessageBroker\NullMessageBroker;
use App\Infrastructure\MessageBroker\RabbitMQBroker;
use App\Interfaces\MessageBrokerInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MessageBrokerInterface::class, function ($app) {
            return match(config('services.message_broker.driver', 'null')) {
                'rabbitmq' => new RabbitMQBroker(),
                'kafka' => new KafkaBroker(),
                default => new NullMessageBroker(),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}

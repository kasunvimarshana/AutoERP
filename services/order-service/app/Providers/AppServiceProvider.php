<?php

namespace App\Providers;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use App\MessageBroker\KafkaBroker;
use App\MessageBroker\NullBroker;
use App\MessageBroker\RabbitMQBroker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MessageBrokerInterface::class, function ($app) {
            $driver = config('services.message_broker.driver', 'null');

            return match ($driver) {
                'rabbitmq' => new RabbitMQBroker(),
                'kafka'    => new KafkaBroker(),
                default    => new NullBroker(),
            };
        });
    }

    public function boot(): void {}
}

<?php

namespace App\Providers;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use App\MessageBroker\MessageBrokerManager;
use Illuminate\Support\ServiceProvider;

class MessageBrokerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MessageBrokerManager::class, fn () => new MessageBrokerManager());

        // Bind the interface to the manager so it can be injected by type
        $this->app->bind(
            MessageBrokerInterface::class,
            MessageBrokerManager::class
        );

        // Convenient facade alias
        $this->app->alias(MessageBrokerManager::class, 'message-broker');
    }

    public function provides(): array
    {
        return [
            MessageBrokerManager::class,
            MessageBrokerInterface::class,
            'message-broker',
        ];
    }
}

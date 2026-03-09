<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Domain\Contracts\MessageBrokerInterface;
use App\Infrastructure\Messaging\MessageBrokerManager;

class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MessageBrokerManager::class);
        $this->app->bind(MessageBrokerInterface::class, function ($app) {
            return $app->make(MessageBrokerManager::class);
        });
    }
    public function boot(): void {}
}

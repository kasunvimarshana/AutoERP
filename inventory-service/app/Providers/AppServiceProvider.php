<?php

namespace App\Providers;

use App\Messaging\RabbitMQConsumer;
use App\Messaging\RabbitMQPublisher;
use App\Services\InventoryService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RabbitMQPublisher::class, fn () => new RabbitMQPublisher());
        $this->app->singleton(RabbitMQConsumer::class,  fn () => new RabbitMQConsumer());
        $this->app->singleton(InventoryService::class,  fn () => new InventoryService());
    }

    public function boot(): void {}
}

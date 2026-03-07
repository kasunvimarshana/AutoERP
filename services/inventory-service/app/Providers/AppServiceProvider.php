<?php

namespace App\Providers;

use App\Saga\InventorySagaHandler;
use App\Services\InventoryService;
use App\Services\RabbitMQService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RabbitMQService::class);
        $this->app->singleton(InventoryService::class);

        $this->app->singleton(InventorySagaHandler::class, fn ($app) =>
            new InventorySagaHandler(
                $app->make(InventoryService::class),
                $app->make(RabbitMQService::class),
            )
        );
    }

    public function boot(): void
    {
        //
    }
}

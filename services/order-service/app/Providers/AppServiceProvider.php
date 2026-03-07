<?php

namespace App\Providers;

use App\Saga\OrderSagaOrchestrator;
use App\Saga\Steps\CreateOrderStep;
use App\Saga\Steps\ProcessPaymentStep;
use App\Saga\Steps\ReserveInventoryStep;
use App\Saga\Steps\SendNotificationStep;
use App\Services\RabbitMQService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind RabbitMQService as a singleton so all injections share one connection.
        $this->app->singleton(RabbitMQService::class);

        // Bind saga steps as singletons to avoid re-instantiation.
        $this->app->singleton(CreateOrderStep::class);

        $this->app->singleton(ReserveInventoryStep::class, fn ($app) =>
            new ReserveInventoryStep($app->make(RabbitMQService::class))
        );

        $this->app->singleton(ProcessPaymentStep::class, fn ($app) =>
            new ProcessPaymentStep($app->make(RabbitMQService::class))
        );

        $this->app->singleton(SendNotificationStep::class, fn ($app) =>
            new SendNotificationStep($app->make(RabbitMQService::class))
        );

        // Bind the orchestrator as a singleton.
        $this->app->singleton(OrderSagaOrchestrator::class, fn ($app) =>
            new OrderSagaOrchestrator(
                $app->make(CreateOrderStep::class),
                $app->make(ReserveInventoryStep::class),
                $app->make(ProcessPaymentStep::class),
                $app->make(SendNotificationStep::class),
                $app->make(RabbitMQService::class),
            )
        );
    }

    public function boot(): void
    {
        //
    }
}

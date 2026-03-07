<?php

namespace App\Providers;

use App\Messaging\RabbitMQConsumer;
use App\Messaging\RabbitMQPublisher;
use App\Saga\SagaOrchestrator;
use App\Saga\Steps\ProcessPaymentStep;
use App\Saga\Steps\RefundPaymentStep;
use App\Saga\Steps\ReleaseInventoryStep;
use App\Saga\Steps\ReserveInventoryStep;
use App\Saga\Steps\SendNotificationStep;
use Illuminate\Support\ServiceProvider;
use Predis\Client as RedisClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RedisClient::class, function () {
            return new RedisClient([
                'scheme'   => 'tcp',
                'host'     => config('database.redis.default.host', '127.0.0.1'),
                'port'     => (int) config('database.redis.default.port', 6379),
                'password' => config('database.redis.default.password') ?: null,
                'database' => (int) config('database.redis.default.database', 0),
            ]);
        });

        $this->app->singleton(RabbitMQPublisher::class, fn () => new RabbitMQPublisher());
        $this->app->singleton(RabbitMQConsumer::class,  fn () => new RabbitMQConsumer());

        $this->app->singleton(ReserveInventoryStep::class, fn ($app) => new ReserveInventoryStep(
            $app->make(RabbitMQPublisher::class)
        ));
        $this->app->singleton(ProcessPaymentStep::class, fn ($app) => new ProcessPaymentStep(
            $app->make(RabbitMQPublisher::class)
        ));
        $this->app->singleton(SendNotificationStep::class, fn ($app) => new SendNotificationStep(
            $app->make(RabbitMQPublisher::class)
        ));
        $this->app->singleton(ReleaseInventoryStep::class, fn ($app) => new ReleaseInventoryStep(
            $app->make(RabbitMQPublisher::class)
        ));
        $this->app->singleton(RefundPaymentStep::class, fn ($app) => new RefundPaymentStep(
            $app->make(RabbitMQPublisher::class)
        ));

        $this->app->singleton(SagaOrchestrator::class, fn ($app) => new SagaOrchestrator(
            $app->make(ReserveInventoryStep::class),
            $app->make(ProcessPaymentStep::class),
            $app->make(SendNotificationStep::class),
            $app->make(ReleaseInventoryStep::class),
            $app->make(RefundPaymentStep::class),
            $app->make(RedisClient::class)
        ));
    }

    public function boot(): void
    {
        //
    }
}

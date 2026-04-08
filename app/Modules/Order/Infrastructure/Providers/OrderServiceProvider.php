<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Order\Application\Contracts\OrderServiceInterface;
use Modules\Order\Application\Services\OrderService;
use Modules\Order\Domain\RepositoryInterfaces\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Http\Controllers\OrderController;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\OrderModel;
use Modules\Order\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrderRepository;

final class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrderRepositoryInterface::class,
            static fn ($app) => new EloquentOrderRepository($app->make(OrderModel::class))
        );

        $this->app->singleton(
            OrderServiceInterface::class,
            static fn ($app) => new OrderService(
                $app->make(OrderRepositoryInterface::class)
            )
        );

        $this->mergeConfigFrom(__DIR__ . '/../../config/order.php', 'order');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerRoutes();

        $this->publishes([
            __DIR__ . '/../../config/order.php' => config_path('order.php'),
        ], 'order-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'order-migrations');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:api'])
            ->prefix('api/order')
            ->group(static function (): void {
                Route::apiResource('orders', OrderController::class);
                Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus']);
            });
    }
}

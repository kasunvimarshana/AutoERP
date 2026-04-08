<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Returns\Application\Contracts\ReturnServiceInterface;
use Modules\Returns\Application\Services\ReturnService;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnRepositoryInterface;
use Modules\Returns\Infrastructure\Http\Controllers\ReturnController;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\ReturnModel;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentReturnRepository;

final class ReturnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ReturnRepositoryInterface::class,
            static fn ($app) => new EloquentReturnRepository($app->make(ReturnModel::class))
        );

        $this->app->singleton(
            ReturnServiceInterface::class,
            static fn ($app) => new ReturnService(
                $app->make(ReturnRepositoryInterface::class)
            )
        );

        $this->mergeConfigFrom(__DIR__ . '/../../config/returns.php', 'returns');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerRoutes();

        $this->publishes([
            __DIR__ . '/../../config/returns.php' => config_path('returns.php'),
        ], 'returns-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'returns-migrations');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:api'])
            ->prefix('api/return')
            ->group(static function (): void {
                Route::apiResource('returns', ReturnController::class);
                Route::post('returns/{id}/approve', [ReturnController::class, 'approve']);
                Route::post('returns/{id}/reject', [ReturnController::class, 'reject']);
                Route::post('returns/{id}/process', [ReturnController::class, 'process']);
            });
    }
}

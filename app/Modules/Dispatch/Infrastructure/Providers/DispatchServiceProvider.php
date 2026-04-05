<?php
declare(strict_types=1);
namespace Modules\Dispatch\Infrastructure\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchLineModel;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchModel;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories\EloquentDispatchRepository;
class DispatchServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(DispatchRepositoryInterface::class, fn($app) =>
            new EloquentDispatchRepository($app->make(DispatchModel::class),$app->make(DispatchLineModel::class))
        );
    }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}

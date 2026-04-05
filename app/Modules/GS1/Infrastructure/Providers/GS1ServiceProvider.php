<?php
declare(strict_types=1);
namespace Modules\GS1\Infrastructure\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1LabelRepositoryInterface;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\Gs1LabelModel;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories\EloquentGs1LabelRepository;
class GS1ServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(Gs1LabelRepositoryInterface::class, fn($app) => new EloquentGs1LabelRepository($app->make(Gs1LabelModel::class)));
    }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}

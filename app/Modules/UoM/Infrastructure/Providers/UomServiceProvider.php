<?php
declare(strict_types=1);
namespace Modules\UoM\Infrastructure\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\UoM\Application\Contracts\UomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\UnitOfMeasureServiceInterface;
use Modules\UoM\Application\Services\UomCategoryService;
use Modules\UoM\Application\Services\UnitOfMeasureService;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomCategoryModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomCategoryRepository;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;
class UomServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(UomCategoryRepositoryInterface::class, fn($app) => new EloquentUomCategoryRepository($app->make(UomCategoryModel::class)));
        $this->app->bind(UnitOfMeasureRepositoryInterface::class, fn($app) => new EloquentUnitOfMeasureRepository($app->make(UnitOfMeasureModel::class)));
        $this->app->bind(UomCategoryServiceInterface::class, fn($app) => new UomCategoryService($app->make(UomCategoryRepositoryInterface::class)));
        $this->app->bind(UnitOfMeasureServiceInterface::class, fn($app) => new UnitOfMeasureService($app->make(UnitOfMeasureRepositoryInterface::class)));
    }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}

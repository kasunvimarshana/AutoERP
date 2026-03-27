<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Category\Application\Contracts\CreateCategoryServiceInterface;
use Modules\Category\Application\Contracts\DeleteCategoryImageServiceInterface;
use Modules\Category\Application\Contracts\DeleteCategoryServiceInterface;
use Modules\Category\Application\Contracts\UpdateCategoryServiceInterface;
use Modules\Category\Application\Contracts\UploadCategoryImageServiceInterface;
use Modules\Category\Application\Services\CreateCategoryService;
use Modules\Category\Application\Services\DeleteCategoryImageService;
use Modules\Category\Application\Services\DeleteCategoryService;
use Modules\Category\Application\Services\UpdateCategoryService;
use Modules\Category\Application\Services\UploadCategoryImageService;
use Modules\Category\Domain\RepositoryInterfaces\CategoryImageRepositoryInterface;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Category\Infrastructure\Persistence\Eloquent\Models\CategoryImageModel;
use Modules\Category\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use Modules\Category\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryImageRepository;
use Modules\Category\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryRepository;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class CategoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, function ($app) {
            return new EloquentCategoryRepository($app->make(CategoryModel::class));
        });

        $this->app->bind(CategoryImageRepositoryInterface::class, function ($app) {
            return new EloquentCategoryImageRepository($app->make(CategoryImageModel::class));
        });

        $this->app->bind(CreateCategoryServiceInterface::class, function ($app) {
            return new CreateCategoryService($app->make(CategoryRepositoryInterface::class));
        });

        $this->app->bind(UpdateCategoryServiceInterface::class, function ($app) {
            return new UpdateCategoryService($app->make(CategoryRepositoryInterface::class));
        });

        $this->app->bind(DeleteCategoryServiceInterface::class, function ($app) {
            return new DeleteCategoryService($app->make(CategoryRepositoryInterface::class));
        });

        $this->app->bind(UploadCategoryImageServiceInterface::class, function ($app) {
            return new UploadCategoryImageService(
                $app->make(CategoryRepositoryInterface::class),
                $app->make(CategoryImageRepositoryInterface::class),
                $app->make(FileStorageServiceInterface::class)
            );
        });

        $this->app->bind(DeleteCategoryImageServiceInterface::class, function ($app) {
            return new DeleteCategoryImageService(
                $app->make(CategoryImageRepositoryInterface::class),
                $app->make(FileStorageServiceInterface::class)
            );
        });
    }

    public function boot(): void
    {
        Route::middleware('api')
             ->prefix('api')
             ->group(function () {
                 $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
             });

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}

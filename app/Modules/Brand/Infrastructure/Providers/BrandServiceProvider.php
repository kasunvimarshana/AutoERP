<?php

declare(strict_types=1);

namespace Modules\Brand\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Brand\Application\Contracts\CreateBrandServiceInterface;
use Modules\Brand\Application\Contracts\DeleteBrandLogoServiceInterface;
use Modules\Brand\Application\Contracts\DeleteBrandServiceInterface;
use Modules\Brand\Application\Contracts\UpdateBrandServiceInterface;
use Modules\Brand\Application\Contracts\UploadBrandLogoServiceInterface;
use Modules\Brand\Application\Services\CreateBrandService;
use Modules\Brand\Application\Services\DeleteBrandLogoService;
use Modules\Brand\Application\Services\DeleteBrandService;
use Modules\Brand\Application\Services\UpdateBrandService;
use Modules\Brand\Application\Services\UploadBrandLogoService;
use Modules\Brand\Domain\RepositoryInterfaces\BrandLogoRepositoryInterface;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Models\BrandLogoModel;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Models\BrandModel;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Repositories\EloquentBrandLogoRepository;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Repositories\EloquentBrandRepository;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class BrandServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BrandRepositoryInterface::class, function ($app) {
            return new EloquentBrandRepository($app->make(BrandModel::class));
        });

        $this->app->bind(BrandLogoRepositoryInterface::class, function ($app) {
            return new EloquentBrandLogoRepository($app->make(BrandLogoModel::class));
        });

        $this->app->bind(CreateBrandServiceInterface::class, function ($app) {
            return new CreateBrandService($app->make(BrandRepositoryInterface::class));
        });

        $this->app->bind(UpdateBrandServiceInterface::class, function ($app) {
            return new UpdateBrandService($app->make(BrandRepositoryInterface::class));
        });

        $this->app->bind(DeleteBrandServiceInterface::class, function ($app) {
            return new DeleteBrandService($app->make(BrandRepositoryInterface::class));
        });

        $this->app->bind(UploadBrandLogoServiceInterface::class, function ($app) {
            return new UploadBrandLogoService(
                $app->make(BrandRepositoryInterface::class),
                $app->make(BrandLogoRepositoryInterface::class),
                $app->make(FileStorageServiceInterface::class)
            );
        });

        $this->app->bind(DeleteBrandLogoServiceInterface::class, function ($app) {
            return new DeleteBrandLogoService(
                $app->make(BrandLogoRepositoryInterface::class),
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

<?php

declare(strict_types=1);

namespace Modules\Location\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Location\Application\Contracts\CreateLocationServiceInterface;
use Modules\Location\Application\Contracts\DeleteLocationServiceInterface;
use Modules\Location\Application\Contracts\FindLocationServiceInterface;
use Modules\Location\Application\Contracts\MoveLocationServiceInterface;
use Modules\Location\Application\Contracts\UpdateLocationServiceInterface;
use Modules\Location\Application\Services\CreateLocationService;
use Modules\Location\Application\Services\DeleteLocationService;
use Modules\Location\Application\Services\FindLocationService;
use Modules\Location\Application\Services\MoveLocationService;
use Modules\Location\Application\Services\UpdateLocationService;
use Modules\Location\Domain\RepositoryInterfaces\LocationRepositoryInterface;
use Modules\Location\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Modules\Location\Infrastructure\Persistence\Eloquent\Repositories\EloquentLocationRepository;

class LocationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LocationRepositoryInterface::class, function ($app) {
            return new EloquentLocationRepository($app->make(LocationModel::class));
        });

        $this->app->bind(CreateLocationServiceInterface::class, function ($app) {
            return new CreateLocationService($app->make(LocationRepositoryInterface::class));
        });

        $this->app->bind(FindLocationServiceInterface::class, function ($app) {
            return new FindLocationService($app->make(LocationRepositoryInterface::class));
        });

        $this->app->bind(UpdateLocationServiceInterface::class, function ($app) {
            return new UpdateLocationService($app->make(LocationRepositoryInterface::class));
        });

        $this->app->bind(DeleteLocationServiceInterface::class, function ($app) {
            return new DeleteLocationService($app->make(LocationRepositoryInterface::class));
        });

        $this->app->bind(MoveLocationServiceInterface::class, function ($app) {
            return new MoveLocationService($app->make(LocationRepositoryInterface::class));
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

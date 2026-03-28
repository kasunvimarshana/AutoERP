<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Application\Services\CreateSupplierService;
use Modules\Supplier\Application\Services\DeleteSupplierService;
use Modules\Supplier\Application\Services\UpdateSupplierService;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository;

class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SupplierRepositoryInterface::class, function ($app) {
            return new EloquentSupplierRepository($app->make(SupplierModel::class));
        });

        $this->app->bind(CreateSupplierServiceInterface::class, function ($app) {
            return new CreateSupplierService($app->make(SupplierRepositoryInterface::class));
        });

        $this->app->bind(UpdateSupplierServiceInterface::class, function ($app) {
            return new UpdateSupplierService($app->make(SupplierRepositoryInterface::class));
        });

        $this->app->bind(DeleteSupplierServiceInterface::class, function ($app) {
            return new DeleteSupplierService($app->make(SupplierRepositoryInterface::class));
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

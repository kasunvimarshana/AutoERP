<?php
namespace Modules\Supplier\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Supplier\Application\Contracts\CreateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Application\Services\CreateSupplierContactService;
use Modules\Supplier\Application\Services\CreateSupplierService;
use Modules\Supplier\Application\Services\DeleteSupplierContactService;
use Modules\Supplier\Application\Services\DeleteSupplierService;
use Modules\Supplier\Application\Services\UpdateSupplierContactService;
use Modules\Supplier\Application\Services\UpdateSupplierService;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierContactRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository;

class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SupplierRepositoryInterface::class, EloquentSupplierRepository::class);
        $this->app->bind(SupplierContactRepositoryInterface::class, EloquentSupplierContactRepository::class);
        $this->app->bind(CreateSupplierServiceInterface::class, CreateSupplierService::class);
        $this->app->bind(UpdateSupplierServiceInterface::class, UpdateSupplierService::class);
        $this->app->bind(DeleteSupplierServiceInterface::class, DeleteSupplierService::class);
        $this->app->bind(CreateSupplierContactServiceInterface::class, CreateSupplierContactService::class);
        $this->app->bind(UpdateSupplierContactServiceInterface::class, UpdateSupplierContactService::class);
        $this->app->bind(DeleteSupplierContactServiceInterface::class, DeleteSupplierContactService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}

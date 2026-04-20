<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\CreateSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierProductServiceInterface;
use Modules\Supplier\Application\Services\CreateSupplierAddressService;
use Modules\Supplier\Application\Services\CreateSupplierContactService;
use Modules\Supplier\Application\Services\CreateSupplierProductService;
use Modules\Supplier\Application\Services\CreateSupplierService;
use Modules\Supplier\Application\Services\DeleteSupplierAddressService;
use Modules\Supplier\Application\Services\DeleteSupplierContactService;
use Modules\Supplier\Application\Services\DeleteSupplierProductService;
use Modules\Supplier\Application\Services\DeleteSupplierService;
use Modules\Supplier\Application\Services\FindSupplierAddressService;
use Modules\Supplier\Application\Services\FindSupplierContactService;
use Modules\Supplier\Application\Services\FindSupplierProductService;
use Modules\Supplier\Application\Services\FindSupplierService;
use Modules\Supplier\Application\Services\UpdateSupplierAddressService;
use Modules\Supplier\Application\Services\UpdateSupplierContactService;
use Modules\Supplier\Application\Services\UpdateSupplierProductService;
use Modules\Supplier\Application\Services\UpdateSupplierService;
use Modules\Supplier\Domain\Contracts\SupplierUserSynchronizerInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierAddressRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierProductRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierAddressRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierContactRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierProductRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository;
use Modules\Supplier\Infrastructure\Services\EloquentSupplierUserSynchronizer;

class SupplierServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(SupplierRepositoryInterface::class, EloquentSupplierRepository::class);
        $this->app->bind(SupplierAddressRepositoryInterface::class, EloquentSupplierAddressRepository::class);
        $this->app->bind(SupplierContactRepositoryInterface::class, EloquentSupplierContactRepository::class);
        $this->app->bind(SupplierProductRepositoryInterface::class, EloquentSupplierProductRepository::class);
        $this->app->bind(SupplierUserSynchronizerInterface::class, EloquentSupplierUserSynchronizer::class);

        $this->app->bind(CreateSupplierServiceInterface::class, CreateSupplierService::class);
        $this->app->bind(FindSupplierServiceInterface::class, FindSupplierService::class);
        $this->app->bind(UpdateSupplierServiceInterface::class, UpdateSupplierService::class);
        $this->app->bind(DeleteSupplierServiceInterface::class, DeleteSupplierService::class);

        $this->app->bind(CreateSupplierAddressServiceInterface::class, CreateSupplierAddressService::class);
        $this->app->bind(FindSupplierAddressServiceInterface::class, FindSupplierAddressService::class);
        $this->app->bind(UpdateSupplierAddressServiceInterface::class, UpdateSupplierAddressService::class);
        $this->app->bind(DeleteSupplierAddressServiceInterface::class, DeleteSupplierAddressService::class);

        $this->app->bind(CreateSupplierContactServiceInterface::class, CreateSupplierContactService::class);
        $this->app->bind(FindSupplierContactServiceInterface::class, FindSupplierContactService::class);
        $this->app->bind(UpdateSupplierContactServiceInterface::class, UpdateSupplierContactService::class);
        $this->app->bind(DeleteSupplierContactServiceInterface::class, DeleteSupplierContactService::class);

        $this->app->bind(CreateSupplierProductServiceInterface::class, CreateSupplierProductService::class);
        $this->app->bind(FindSupplierProductServiceInterface::class, FindSupplierProductService::class);
        $this->app->bind(UpdateSupplierProductServiceInterface::class, UpdateSupplierProductService::class);
        $this->app->bind(DeleteSupplierProductServiceInterface::class, DeleteSupplierProductService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}

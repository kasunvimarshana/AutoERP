<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\CRM\Application\Contracts\CustomerServiceInterface;
use Modules\CRM\Application\Contracts\SupplierServiceInterface;
use Modules\CRM\Application\Services\CustomerService;
use Modules\CRM\Application\Services\SupplierService;
use Modules\CRM\Domain\Contracts\Repositories\ContactRepositoryInterface;
use Modules\CRM\Domain\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\CRM\Domain\Contracts\Repositories\SupplierRepositoryInterface;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\ContactModel;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Repositories\EloquentContactRepository;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerRepository;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository;

class CRMServiceProvider extends ServiceProvider
{
    /**
     * Register CRM module bindings.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(CustomerRepositoryInterface::class, function ($app) {
            return new EloquentCustomerRepository($app->make(CustomerModel::class));
        });

        $this->app->bind(SupplierRepositoryInterface::class, function ($app) {
            return new EloquentSupplierRepository($app->make(SupplierModel::class));
        });

        $this->app->bind(ContactRepositoryInterface::class, function ($app) {
            return new EloquentContactRepository($app->make(ContactModel::class));
        });

        // Services
        $this->app->bind(CustomerServiceInterface::class, function ($app) {
            return new CustomerService($app->make(CustomerRepositoryInterface::class));
        });

        $this->app->bind(SupplierServiceInterface::class, function ($app) {
            return new SupplierService($app->make(SupplierRepositoryInterface::class));
        });
    }

    /**
     * Boot the CRM service provider.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}

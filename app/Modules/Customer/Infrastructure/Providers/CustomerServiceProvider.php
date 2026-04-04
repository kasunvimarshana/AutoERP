<?php
namespace Modules\Customer\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Customer\Application\Contracts\CreateCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Application\Services\CreateCustomerAddressService;
use Modules\Customer\Application\Services\CreateCustomerService;
use Modules\Customer\Application\Services\DeleteCustomerAddressService;
use Modules\Customer\Application\Services\DeleteCustomerService;
use Modules\Customer\Application\Services\UpdateCustomerAddressService;
use Modules\Customer\Application\Services\UpdateCustomerService;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerAddressRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerRepository;

class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, EloquentCustomerRepository::class);
        $this->app->bind(CustomerAddressRepositoryInterface::class, EloquentCustomerAddressRepository::class);
        $this->app->bind(CreateCustomerServiceInterface::class, CreateCustomerService::class);
        $this->app->bind(UpdateCustomerServiceInterface::class, UpdateCustomerService::class);
        $this->app->bind(DeleteCustomerServiceInterface::class, DeleteCustomerService::class);
        $this->app->bind(CreateCustomerAddressServiceInterface::class, CreateCustomerAddressService::class);
        $this->app->bind(UpdateCustomerAddressServiceInterface::class, UpdateCustomerAddressService::class);
        $this->app->bind(DeleteCustomerAddressServiceInterface::class, DeleteCustomerAddressService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}

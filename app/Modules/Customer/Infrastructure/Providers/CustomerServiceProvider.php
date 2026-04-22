<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Customer\Application\Contracts\CreateCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\CreateCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Application\Services\CreateCustomerAddressService;
use Modules\Customer\Application\Services\CreateCustomerContactService;
use Modules\Customer\Application\Services\CreateCustomerService;
use Modules\Customer\Application\Services\DeleteCustomerAddressService;
use Modules\Customer\Application\Services\DeleteCustomerContactService;
use Modules\Customer\Application\Services\DeleteCustomerService;
use Modules\Customer\Application\Services\FindCustomerAddressService;
use Modules\Customer\Application\Services\FindCustomerContactService;
use Modules\Customer\Application\Services\FindCustomerService;
use Modules\Customer\Application\Services\UpdateCustomerAddressService;
use Modules\Customer\Application\Services\UpdateCustomerContactService;
use Modules\Customer\Application\Services\UpdateCustomerService;
use Modules\Customer\Domain\Contracts\CustomerUserSynchronizerInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerContactRepositoryInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerAddressRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerContactRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerRepository;
use Modules\Customer\Infrastructure\Services\EloquentCustomerUserSynchronizer;

class CustomerServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, EloquentCustomerRepository::class);
        $this->app->bind(CustomerAddressRepositoryInterface::class, EloquentCustomerAddressRepository::class);
        $this->app->bind(CustomerContactRepositoryInterface::class, EloquentCustomerContactRepository::class);
        $this->app->bind(CustomerUserSynchronizerInterface::class, EloquentCustomerUserSynchronizer::class);

        $this->app->bind(CreateCustomerServiceInterface::class, CreateCustomerService::class);
        $this->app->bind(FindCustomerServiceInterface::class, FindCustomerService::class);
        $this->app->bind(UpdateCustomerServiceInterface::class, UpdateCustomerService::class);
        $this->app->bind(DeleteCustomerServiceInterface::class, DeleteCustomerService::class);

        $this->app->bind(CreateCustomerAddressServiceInterface::class, CreateCustomerAddressService::class);
        $this->app->bind(FindCustomerAddressServiceInterface::class, FindCustomerAddressService::class);
        $this->app->bind(UpdateCustomerAddressServiceInterface::class, UpdateCustomerAddressService::class);
        $this->app->bind(DeleteCustomerAddressServiceInterface::class, DeleteCustomerAddressService::class);

        $this->app->bind(CreateCustomerContactServiceInterface::class, CreateCustomerContactService::class);
        $this->app->bind(FindCustomerContactServiceInterface::class, FindCustomerContactService::class);
        $this->app->bind(UpdateCustomerContactServiceInterface::class, UpdateCustomerContactService::class);
        $this->app->bind(DeleteCustomerContactServiceInterface::class, DeleteCustomerContactService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}

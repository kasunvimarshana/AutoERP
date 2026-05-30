<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Customer\Application\Contracts\Services\CustomerManagementServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\CreateCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\DeleteCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\GetCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\ListCustomerAddressesServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\UpdateCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\CreateCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\DeleteCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\GetCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\ListCustomerContactsServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\UpdateCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\Customers\CreateCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\Customers\DeleteCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\Customers\GetCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\Customers\ListCustomersServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\Customers\UpdateCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerVehicles\CreateCustomerVehicleServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerVehicles\DeleteCustomerVehicleServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerVehicles\GetCustomerVehicleServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerVehicles\ListCustomerVehiclesServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerVehicles\UpdateCustomerVehicleServiceInterface;
use Modules\Customer\Application\Repositories\CustomerAddressRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerCategoryRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerContactRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerCreditProfileRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerStatusHistoryRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerTaxProfileRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerUserAccountRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerVehicleRepositoryInterface;
use Modules\Customer\Application\UseCases\CustomerAddresses\CreateCustomerAddressService;
use Modules\Customer\Application\UseCases\CustomerAddresses\DeleteCustomerAddressService;
use Modules\Customer\Application\UseCases\CustomerAddresses\GetCustomerAddressService;
use Modules\Customer\Application\UseCases\CustomerAddresses\ListCustomerAddressesService;
use Modules\Customer\Application\UseCases\CustomerAddresses\UpdateCustomerAddressService;
use Modules\Customer\Application\UseCases\CustomerContacts\CreateCustomerContactService;
use Modules\Customer\Application\UseCases\CustomerContacts\DeleteCustomerContactService;
use Modules\Customer\Application\UseCases\CustomerContacts\GetCustomerContactService;
use Modules\Customer\Application\UseCases\CustomerContacts\ListCustomerContactsService;
use Modules\Customer\Application\UseCases\CustomerContacts\UpdateCustomerContactService;
use Modules\Customer\Application\UseCases\Customers\CreateCustomerService;
use Modules\Customer\Application\UseCases\Customers\DeleteCustomerService;
use Modules\Customer\Application\UseCases\Customers\GetCustomerService;
use Modules\Customer\Application\UseCases\Customers\ListCustomersService;
use Modules\Customer\Application\UseCases\Customers\UpdateCustomerService;
use Modules\Customer\Application\UseCases\CustomerVehicles\CreateCustomerVehicleService;
use Modules\Customer\Application\UseCases\CustomerVehicles\DeleteCustomerVehicleService;
use Modules\Customer\Application\UseCases\CustomerVehicles\GetCustomerVehicleService;
use Modules\Customer\Application\UseCases\CustomerVehicles\ListCustomerVehiclesService;
use Modules\Customer\Application\UseCases\CustomerVehicles\UpdateCustomerVehicleService;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerAddressModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerCategoryModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerContactModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerCreditProfileModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerStatusHistoryModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerTaxProfileModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerUserAccountModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerVehicleModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerAddressRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerCategoryRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerContactRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerCreditProfileRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerStatusHistoryRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerTaxProfileRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerUserAccountRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerVehicleRepository;
use Modules\Customer\Infrastructure\Services\CustomerManagementService;

final class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/customer.php', 'customer');

        foreach (
            [
                ListCustomersServiceInterface::class => ListCustomersService::class,
                GetCustomerServiceInterface::class => GetCustomerService::class,
                CreateCustomerServiceInterface::class => CreateCustomerService::class,
                UpdateCustomerServiceInterface::class => UpdateCustomerService::class,
                DeleteCustomerServiceInterface::class => DeleteCustomerService::class,
                ListCustomerContactsServiceInterface::class => ListCustomerContactsService::class,
                GetCustomerContactServiceInterface::class => GetCustomerContactService::class,
                CreateCustomerContactServiceInterface::class => CreateCustomerContactService::class,
                UpdateCustomerContactServiceInterface::class => UpdateCustomerContactService::class,
                DeleteCustomerContactServiceInterface::class => DeleteCustomerContactService::class,
                ListCustomerAddressesServiceInterface::class => ListCustomerAddressesService::class,
                GetCustomerAddressServiceInterface::class => GetCustomerAddressService::class,
                CreateCustomerAddressServiceInterface::class => CreateCustomerAddressService::class,
                UpdateCustomerAddressServiceInterface::class => UpdateCustomerAddressService::class,
                DeleteCustomerAddressServiceInterface::class => DeleteCustomerAddressService::class,
                ListCustomerVehiclesServiceInterface::class => ListCustomerVehiclesService::class,
                GetCustomerVehicleServiceInterface::class => GetCustomerVehicleService::class,
                CreateCustomerVehicleServiceInterface::class => CreateCustomerVehicleService::class,
                UpdateCustomerVehicleServiceInterface::class => UpdateCustomerVehicleService::class,
                DeleteCustomerVehicleServiceInterface::class => DeleteCustomerVehicleService::class,
                CustomerManagementServiceInterface::class => CustomerManagementService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(CustomerRepositoryInterface::class, function (): CustomerRepositoryInterface {
            return new EloquentCustomerRepository(new CustomerModel);
        });
        $this->app->singleton(
            CustomerContactRepositoryInterface::class,
            static function (): CustomerContactRepositoryInterface {
                return new EloquentCustomerContactRepository(new CustomerContactModel);
            },
        );
        $this->app->singleton(
            CustomerAddressRepositoryInterface::class,
            static function (): CustomerAddressRepositoryInterface {
                return new EloquentCustomerAddressRepository(new CustomerAddressModel);
            },
        );
        $this->app->singleton(
            CustomerVehicleRepositoryInterface::class,
            static function (): CustomerVehicleRepositoryInterface {
                return new EloquentCustomerVehicleRepository(new CustomerVehicleModel);
            },
        );
        $this->app->singleton(
            CustomerCategoryRepositoryInterface::class,
            static function (): CustomerCategoryRepositoryInterface {
                return new EloquentCustomerCategoryRepository(new CustomerCategoryModel);
            },
        );
        $this->app->singleton(
            CustomerTaxProfileRepositoryInterface::class,
            static function (): CustomerTaxProfileRepositoryInterface {
                return new EloquentCustomerTaxProfileRepository(new CustomerTaxProfileModel);
            },
        );
        $this->app->singleton(
            CustomerCreditProfileRepositoryInterface::class,
            static function (): CustomerCreditProfileRepositoryInterface {
                return new EloquentCustomerCreditProfileRepository(new CustomerCreditProfileModel);
            },
        );
        $this->app->singleton(
            CustomerUserAccountRepositoryInterface::class,
            static function (): CustomerUserAccountRepositoryInterface {
                return new EloquentCustomerUserAccountRepository(new CustomerUserAccountModel);
            },
        );
        $this->app->singleton(
            CustomerStatusHistoryRepositoryInterface::class,
            static function (): CustomerStatusHistoryRepositoryInterface {
                return new EloquentCustomerStatusHistoryRepository(new CustomerStatusHistoryModel);
            },
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}

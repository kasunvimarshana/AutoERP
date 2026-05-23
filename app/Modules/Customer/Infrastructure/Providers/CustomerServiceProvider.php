<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Customer\Application\Repositories\CustomerAddressRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerContactRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerVehicleRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerAddressRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerContactRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerRepository;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerVehicleRepository;

class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/customer.php', 'customer');

        foreach ([
            CustomerAddressRepositoryInterface::class => EloquentCustomerAddressRepository::class,
            CustomerContactRepositoryInterface::class => EloquentCustomerContactRepository::class,
            CustomerRepositoryInterface::class => EloquentCustomerRepository::class,
            CustomerVehicleRepositoryInterface::class => EloquentCustomerVehicleRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}

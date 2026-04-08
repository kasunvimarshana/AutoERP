<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;
use Modules\Customer\Application\Contracts\DeleteCustomerServiceInterface;
use Modules\Customer\Application\Contracts\FindCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UpdateCustomerServiceInterface;
use Modules\Customer\Application\Services\CreateCustomerService;
use Modules\Customer\Application\Services\DeleteCustomerService;
use Modules\Customer\Application\Services\FindCustomerService;
use Modules\Customer\Application\Services\UpdateCustomerService;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerRepository;

class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, function ($app) {
            return new EloquentCustomerRepository($app->make(CustomerModel::class));
        });

        $this->app->bind(CreateCustomerServiceInterface::class, function ($app) {
            return new CreateCustomerService($app->make(CustomerRepositoryInterface::class));
        });

        $this->app->bind(FindCustomerServiceInterface::class, function ($app) {
            return new FindCustomerService($app->make(CustomerRepositoryInterface::class));
        });

        $this->app->bind(UpdateCustomerServiceInterface::class, function ($app) {
            return new UpdateCustomerService($app->make(CustomerRepositoryInterface::class));
        });

        $this->app->bind(DeleteCustomerServiceInterface::class, function ($app) {
            return new DeleteCustomerService($app->make(CustomerRepositoryInterface::class));
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

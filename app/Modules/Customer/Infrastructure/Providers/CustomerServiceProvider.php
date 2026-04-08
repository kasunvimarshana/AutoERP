<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Customer\Application\Contracts\CustomerServiceInterface;
use Modules\Customer\Application\Services\CustomerService;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Http\Controllers\CustomerController;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerRepository;

final class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CustomerRepositoryInterface::class,
            static fn ($app) => new EloquentCustomerRepository($app->make(CustomerModel::class))
        );

        $this->app->singleton(
            CustomerServiceInterface::class,
            static fn ($app) => new CustomerService(
                $app->make(CustomerRepositoryInterface::class)
            )
        );

        $this->mergeConfigFrom(__DIR__ . '/../../config/customer.php', 'customer');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerRoutes();

        $this->publishes([
            __DIR__ . '/../../config/customer.php' => config_path('customer.php'),
        ], 'customer-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'customer-migrations');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:api'])
            ->prefix('api/customer')
            ->group(static function (): void {
                Route::apiResource('customers', CustomerController::class);
            });
    }
}

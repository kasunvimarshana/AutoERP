<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Customer\Application\Contracts\CustomerServiceInterface;
use Modules\Customer\Application\Services\CustomerService;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerRepository;

class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, EloquentCustomerRepository::class);
        $this->app->bind(CustomerServiceInterface::class, CustomerService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Employee\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Employee\Application\Contracts\CreateEmployeeServiceInterface;
use Modules\Employee\Application\Contracts\DeleteEmployeeServiceInterface;
use Modules\Employee\Application\Contracts\FindEmployeeServiceInterface;
use Modules\Employee\Application\Contracts\UpdateEmployeeServiceInterface;
use Modules\Employee\Application\Services\CreateEmployeeService;
use Modules\Employee\Application\Services\DeleteEmployeeService;
use Modules\Employee\Application\Services\FindEmployeeService;
use Modules\Employee\Application\Services\UpdateEmployeeService;
use Modules\Employee\Domain\Contracts\EmployeeUserSynchronizerInterface;
use Modules\Employee\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use Modules\Employee\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeRepository;
use Modules\Employee\Infrastructure\Services\EloquentEmployeeUserSynchronizer;

class EmployeeServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(EmployeeRepositoryInterface::class, EloquentEmployeeRepository::class);
        $this->app->bind(EmployeeUserSynchronizerInterface::class, EloquentEmployeeUserSynchronizer::class);

        $this->app->bind(CreateEmployeeServiceInterface::class, CreateEmployeeService::class);
        $this->app->bind(FindEmployeeServiceInterface::class, FindEmployeeService::class);
        $this->app->bind(UpdateEmployeeServiceInterface::class, UpdateEmployeeService::class);
        $this->app->bind(DeleteEmployeeServiceInterface::class, DeleteEmployeeService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}

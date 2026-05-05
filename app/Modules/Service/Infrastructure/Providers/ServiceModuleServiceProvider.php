<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Service\Application\Contracts\CompleteServiceJobCardServiceInterface;
use Modules\Service\Application\Contracts\CreateServiceJobCardServiceInterface;
use Modules\Service\Application\Contracts\CreateServiceMaintenancePlanServiceInterface;
use Modules\Service\Application\Contracts\FindServiceJobCardServiceInterface;
use Modules\Service\Application\Contracts\FindServiceMaintenancePlanServiceInterface;
use Modules\Service\Application\Contracts\UpdateServiceJobCardStatusServiceInterface;
use Modules\Service\Application\Services\CompleteServiceJobCardService;
use Modules\Service\Application\Services\CreateServiceJobCardService;
use Modules\Service\Application\Services\CreateServiceMaintenancePlanService;
use Modules\Service\Application\Services\FindServiceJobCardService;
use Modules\Service\Application\Services\FindServiceMaintenancePlanService;
use Modules\Service\Application\Services\UpdateServiceJobCardStatusService;
use Modules\Service\Domain\RepositoryInterfaces\ServiceJobCardRepositoryInterface;
use Modules\Service\Domain\RepositoryInterfaces\ServiceMaintenancePlanRepositoryInterface;
use Modules\Service\Infrastructure\Persistence\Eloquent\Repositories\EloquentServiceJobCardRepository;
use Modules\Service\Infrastructure\Persistence\Eloquent\Repositories\EloquentServiceMaintenancePlanRepository;

class ServiceModuleServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        // Repositories
        $this->app->bind(ServiceJobCardRepositoryInterface::class, EloquentServiceJobCardRepository::class);
        $this->app->bind(ServiceMaintenancePlanRepositoryInterface::class, EloquentServiceMaintenancePlanRepository::class);

        // Services
        $this->app->bind(CreateServiceJobCardServiceInterface::class, CreateServiceJobCardService::class);
        $this->app->bind(FindServiceJobCardServiceInterface::class, FindServiceJobCardService::class);
        $this->app->bind(UpdateServiceJobCardStatusServiceInterface::class, UpdateServiceJobCardStatusService::class);
        $this->app->bind(CompleteServiceJobCardServiceInterface::class, CompleteServiceJobCardService::class);
        $this->app->bind(CreateServiceMaintenancePlanServiceInterface::class, CreateServiceMaintenancePlanService::class);
        $this->app->bind(FindServiceMaintenancePlanServiceInterface::class, FindServiceMaintenancePlanService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\ServiceCenter\Application\Contracts\CompleteServiceOrderServiceInterface;
use Modules\ServiceCenter\Application\Contracts\CreateServiceOrderServiceInterface;
use Modules\ServiceCenter\Application\Services\CompleteServiceOrderService;
use Modules\ServiceCenter\Application\Services\CreateServiceOrderService;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceOrderRepositoryInterface;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServicePartUsageRepositoryInterface;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceTaskRepositoryInterface;
use Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Repositories\EloquentServiceOrderRepository;
use Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Repositories\EloquentServicePartUsageRepository;
use Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Repositories\EloquentServiceTaskRepository;

class ServiceCenterServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(ServiceOrderRepositoryInterface::class, EloquentServiceOrderRepository::class);
        $this->app->bind(ServiceTaskRepositoryInterface::class, EloquentServiceTaskRepository::class);
        $this->app->bind(ServicePartUsageRepositoryInterface::class, EloquentServicePartUsageRepository::class);
        $this->app->bind(CreateServiceOrderServiceInterface::class, CreateServiceOrderService::class);
        $this->app->bind(CompleteServiceOrderServiceInterface::class, CompleteServiceOrderService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}

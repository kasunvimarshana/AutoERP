<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Service\Application\Contracts\CancelServiceWorkOrderServiceInterface;
use Modules\Service\Application\Contracts\CompleteServiceWorkOrderServiceInterface;
use Modules\Service\Application\Contracts\CreateServiceWorkOrderServiceInterface;
use Modules\Service\Application\Contracts\UpdateServiceWorkOrderServiceInterface;
use Modules\Service\Application\Services\CancelServiceWorkOrderService;
use Modules\Service\Application\Services\CompleteServiceWorkOrderService;
use Modules\Service\Application\Services\CreateServiceWorkOrderService;
use Modules\Service\Application\Services\UpdateServiceWorkOrderService;
use Modules\Service\Domain\RepositoryInterfaces\ServiceWorkOrderRepositoryInterface;
use Modules\Service\Infrastructure\Persistence\Eloquent\Repositories\EloquentServiceWorkOrderRepository;

class ServiceServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(
            ServiceWorkOrderRepositoryInterface::class,
            EloquentServiceWorkOrderRepository::class,
        );

        $this->app->bind(
            CreateServiceWorkOrderServiceInterface::class,
            CreateServiceWorkOrderService::class,
        );

        $this->app->bind(
            UpdateServiceWorkOrderServiceInterface::class,
            UpdateServiceWorkOrderService::class,
        );

        $this->app->bind(
            CompleteServiceWorkOrderServiceInterface::class,
            CompleteServiceWorkOrderService::class,
        );

        $this->app->bind(
            CancelServiceWorkOrderServiceInterface::class,
            CancelServiceWorkOrderService::class,
        );
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}

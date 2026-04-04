<?php

namespace Modules\Dispatch\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Dispatch\Application\Contracts\CreateDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\DispatchShipmentServiceInterface;
use Modules\Dispatch\Application\Contracts\MarkDeliveredServiceInterface;
use Modules\Dispatch\Application\Contracts\ProcessDispatchServiceInterface;
use Modules\Dispatch\Application\Services\CreateDispatchService;
use Modules\Dispatch\Application\Services\DispatchShipmentService;
use Modules\Dispatch\Application\Services\MarkDeliveredService;
use Modules\Dispatch\Application\Services\ProcessDispatchService;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchLineRepositoryInterface;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories\EloquentDispatchLineRepository;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories\EloquentDispatchRepository;

class DispatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DispatchRepositoryInterface::class, EloquentDispatchRepository::class);
        $this->app->bind(DispatchLineRepositoryInterface::class, EloquentDispatchLineRepository::class);
        $this->app->bind(CreateDispatchServiceInterface::class, CreateDispatchService::class);
        $this->app->bind(ProcessDispatchServiceInterface::class, ProcessDispatchService::class);
        $this->app->bind(DispatchShipmentServiceInterface::class, DispatchShipmentService::class);
        $this->app->bind(MarkDeliveredServiceInterface::class, MarkDeliveredService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}

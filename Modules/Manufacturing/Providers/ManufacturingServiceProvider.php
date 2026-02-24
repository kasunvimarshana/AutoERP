<?php

namespace Modules\Manufacturing\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Manufacturing\Domain\Contracts\BomLineRepositoryInterface;
use Modules\Manufacturing\Domain\Contracts\BomRepositoryInterface;
use Modules\Manufacturing\Domain\Contracts\WorkOrderLineRepositoryInterface;
use Modules\Manufacturing\Domain\Contracts\WorkOrderRepositoryInterface;
use Modules\Manufacturing\Infrastructure\Repositories\BomLineRepository;
use Modules\Manufacturing\Infrastructure\Repositories\BomRepository;
use Modules\Manufacturing\Infrastructure\Repositories\WorkOrderLineRepository;
use Modules\Manufacturing\Infrastructure\Repositories\WorkOrderRepository;

class ManufacturingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BomRepositoryInterface::class, BomRepository::class);
        $this->app->bind(BomLineRepositoryInterface::class, BomLineRepository::class);
        $this->app->bind(WorkOrderRepositoryInterface::class, WorkOrderRepository::class);
        $this->app->bind(WorkOrderLineRepositoryInterface::class, WorkOrderLineRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'manufacturing');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}

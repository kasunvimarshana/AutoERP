<?php
declare(strict_types=1);
namespace Modules\Maintenance\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Maintenance\Application\Services\MaintenanceScheduleService;
use Modules\Maintenance\Application\Services\ServiceOrderService;
use Modules\Maintenance\Domain\RepositoryInterfaces\MaintenanceScheduleRepositoryInterface;
use Modules\Maintenance\Domain\RepositoryInterfaces\ServiceOrderLineRepositoryInterface;
use Modules\Maintenance\Domain\RepositoryInterfaces\ServiceOrderRepositoryInterface;
use Modules\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceScheduleModel;
use Modules\Maintenance\Infrastructure\Persistence\Eloquent\Models\ServiceOrderLineModel;
use Modules\Maintenance\Infrastructure\Persistence\Eloquent\Models\ServiceOrderModel;
use Modules\Maintenance\Infrastructure\Persistence\Eloquent\Repositories\EloquentMaintenanceScheduleRepository;
use Modules\Maintenance\Infrastructure\Persistence\Eloquent\Repositories\EloquentServiceOrderLineRepository;
use Modules\Maintenance\Infrastructure\Persistence\Eloquent\Repositories\EloquentServiceOrderRepository;

class MaintenanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ServiceOrderRepositoryInterface::class, fn($app) =>
            new EloquentServiceOrderRepository($app->make(ServiceOrderModel::class))
        );
        $this->app->bind(ServiceOrderLineRepositoryInterface::class, fn($app) =>
            new EloquentServiceOrderLineRepository($app->make(ServiceOrderLineModel::class))
        );
        $this->app->bind(MaintenanceScheduleRepositoryInterface::class, fn($app) =>
            new EloquentMaintenanceScheduleRepository($app->make(MaintenanceScheduleModel::class))
        );

        $this->app->bind(ServiceOrderService::class, fn($app) =>
            new ServiceOrderService($app->make(ServiceOrderRepositoryInterface::class))
        );
        $this->app->bind(MaintenanceScheduleService::class, fn($app) =>
            new MaintenanceScheduleService(
                $app->make(MaintenanceScheduleRepositoryInterface::class),
                $app->make(ServiceOrderRepositoryInterface::class),
            )
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}

<?php

namespace Modules\Maintenance\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Maintenance\Application\Listeners\HandleInspectionFailedListener;
use Modules\Maintenance\Domain\Contracts\EquipmentRepositoryInterface;
use Modules\Maintenance\Domain\Contracts\MaintenanceOrderRepositoryInterface;
use Modules\Maintenance\Domain\Contracts\MaintenanceRequestRepositoryInterface;
use Modules\Maintenance\Infrastructure\Repositories\EquipmentRepository;
use Modules\Maintenance\Infrastructure\Repositories\MaintenanceOrderRepository;
use Modules\Maintenance\Infrastructure\Repositories\MaintenanceRequestRepository;
use Modules\QualityControl\Domain\Events\InspectionFailed;

class MaintenanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EquipmentRepositoryInterface::class, EquipmentRepository::class);
        $this->app->bind(MaintenanceRequestRepositoryInterface::class, MaintenanceRequestRepository::class);
        $this->app->bind(MaintenanceOrderRepositoryInterface::class, MaintenanceOrderRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'maintenance');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        Event::listen(InspectionFailed::class, HandleInspectionFailedListener::class);
    }
}

<?php

namespace Modules\Logistics\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Logistics\Application\Listeners\HandleSalesOrderConfirmedListener;
use Modules\Logistics\Domain\Contracts\CarrierRepositoryInterface;
use Modules\Logistics\Domain\Contracts\DeliveryLineRepositoryInterface;
use Modules\Logistics\Domain\Contracts\DeliveryOrderRepositoryInterface;
use Modules\Logistics\Domain\Contracts\TrackingEventRepositoryInterface;
use Modules\Logistics\Infrastructure\Repositories\CarrierRepository;
use Modules\Logistics\Infrastructure\Repositories\DeliveryLineRepository;
use Modules\Logistics\Infrastructure\Repositories\DeliveryOrderRepository;
use Modules\Logistics\Infrastructure\Repositories\TrackingEventRepository;
use Modules\Sales\Domain\Events\OrderConfirmed;

class LogisticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CarrierRepositoryInterface::class, CarrierRepository::class);
        $this->app->bind(DeliveryOrderRepositoryInterface::class, DeliveryOrderRepository::class);
        $this->app->bind(DeliveryLineRepositoryInterface::class, DeliveryLineRepository::class);
        $this->app->bind(TrackingEventRepositoryInterface::class, TrackingEventRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'logistics');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        // Cross-module event listener: auto-create a delivery order when a sales order is confirmed
        Event::listen(OrderConfirmed::class, HandleSalesOrderConfirmedListener::class);
    }
}

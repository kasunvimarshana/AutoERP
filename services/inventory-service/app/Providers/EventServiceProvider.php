<?php

namespace App\Providers;

use App\Events\InventoryUpdated;
use App\Events\LowStockDetected;
use App\Events\StockReleased;
use App\Events\StockReserved;
use App\Listeners\NotifyLowStock;
use App\Listeners\PublishInventoryEventToRabbitMQ;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InventoryUpdated::class => [
            PublishInventoryEventToRabbitMQ::class,
        ],

        StockReserved::class => [
            PublishInventoryEventToRabbitMQ::class,
        ],

        StockReleased::class => [
            PublishInventoryEventToRabbitMQ::class,
        ],

        LowStockDetected::class => [
            PublishInventoryEventToRabbitMQ::class,
            NotifyLowStock::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

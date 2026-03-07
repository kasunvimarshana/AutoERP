<?php

namespace App\Providers;

use App\Events\InventoryUpdatedEvent;
use App\Events\OrderCancelledEvent;
use App\Events\OrderCreatedEvent;
use App\Events\OrderStatusChangedEvent;
use App\Listeners\InventoryUpdatedListener;
use App\Listeners\OrderCancelledListener;
use App\Listeners\OrderCreatedListener;
use App\Listeners\OrderStatusChangedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreatedEvent::class => [
            OrderCreatedListener::class,
        ],
        OrderCancelledEvent::class => [
            OrderCancelledListener::class,
        ],
        OrderStatusChangedEvent::class => [
            OrderStatusChangedListener::class,
        ],
        InventoryUpdatedEvent::class => [
            InventoryUpdatedListener::class,
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

<?php

namespace App\Providers;

use App\Events\InventoryUpdatedEvent;
use App\Events\OrderCreatedEvent;
use App\Listeners\InventoryUpdatedListener;
use App\Listeners\OrderCreatedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreatedEvent::class => [
            OrderCreatedListener::class,
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

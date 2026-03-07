<?php

namespace App\Providers;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Listeners\HandleInventoryUpdated;
use App\Listeners\PublishOrderCancelled;
use App\Listeners\PublishOrderCreated;
use App\Listeners\PublishOrderStatusChanged;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [
            PublishOrderCreated::class,
        ],
        OrderCancelled::class => [
            PublishOrderCancelled::class,
        ],
        OrderStatusChanged::class => [
            PublishOrderStatusChanged::class,
            HandleInventoryUpdated::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

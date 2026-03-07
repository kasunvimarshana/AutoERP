<?php

namespace App\Providers;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Listeners\HandleInventoryUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [],
        OrderStatusChanged::class => [],
        OrderCancelled::class => [],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}

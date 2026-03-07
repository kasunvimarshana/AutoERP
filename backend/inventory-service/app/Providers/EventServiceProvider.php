<?php

namespace App\Providers;

use App\Events\InventoryLow;
use App\Events\InventoryUpdated;
use App\Listeners\Inventory\HandleInventoryLow;
use App\Listeners\Inventory\HandleInventoryUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InventoryUpdated::class => [HandleInventoryUpdated::class],
        InventoryLow::class => [HandleInventoryLow::class],
    ];
}

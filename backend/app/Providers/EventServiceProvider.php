<?php

namespace App\Providers;

use App\Modules\Inventory\Events\InventoryUpdated;
use App\Modules\Inventory\Listeners\InventoryEventListener;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderCreated;
use App\Modules\Order\Events\OrderUpdated;
use App\Modules\Order\Listeners\OrderEventListener;
use App\Modules\Product\Events\ProductCreated;
use App\Modules\Product\Events\ProductDeleted;
use App\Modules\Product\Events\ProductUpdated;
use App\Modules\Product\Listeners\ProductEventListener;
use App\Modules\User\Events\UserCreated;
use App\Modules\User\Listeners\UserCreatedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserCreated::class => [
            UserCreatedListener::class,
        ],

        ProductCreated::class => [
            ProductEventListener::class . '@handleProductCreated',
        ],

        ProductUpdated::class => [
            ProductEventListener::class . '@handleProductUpdated',
        ],

        ProductDeleted::class => [
            ProductEventListener::class . '@handleProductDeleted',
        ],

        InventoryUpdated::class => [
            InventoryEventListener::class,
        ],

        OrderCreated::class => [
            OrderEventListener::class . '@handleOrderCreated',
        ],

        OrderUpdated::class => [
            OrderEventListener::class . '@handleOrderUpdated',
        ],

        OrderCancelled::class => [
            OrderEventListener::class . '@handleOrderCancelled',
        ],
    ];

    public function boot(): void
    {
        //
    }
}

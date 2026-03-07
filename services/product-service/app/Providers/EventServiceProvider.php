<?php

namespace App\Providers;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Listeners\PublishProductEventToRabbitMQ;
use App\Listeners\UpdateInventoryOnProductDeletion;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ProductCreated::class => [
            PublishProductEventToRabbitMQ::class,
        ],
        ProductUpdated::class => [
            PublishProductEventToRabbitMQ::class,
        ],
        ProductDeleted::class => [
            PublishProductEventToRabbitMQ::class,
            UpdateInventoryOnProductDeletion::class,
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

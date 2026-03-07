<?php

namespace App\Providers;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Listeners\NotifyInventoryOnProductCreated;
use App\Listeners\NotifyInventoryOnProductDeleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ProductCreated::class => [
            NotifyInventoryOnProductCreated::class,
        ],
        ProductUpdated::class => [
            // Add listeners here, e.g. SyncProductToSearchIndex::class
        ],
        ProductDeleted::class => [
            NotifyInventoryOnProductDeleted::class,
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

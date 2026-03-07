<?php

namespace App\Providers;

use App\Events\InventoryUpdated;
use App\Events\StockDepleted;
use App\Events\StockLow;
use App\Listeners\HandleProductCreated;
use App\Listeners\HandleProductDeleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     * Internal Laravel events are mapped here.
     * External events (product.created / product.deleted) from the message
     * broker are dispatched as objects from the broker consumer command and
     * handled by HandleProductCreated / HandleProductDeleted.
     */
    protected $listen = [
        InventoryUpdated::class => [
            // Add listeners here – e.g. NotifyLowStockSubscribers::class
        ],
        StockLow::class => [
            // e.g. SendLowStockAlert::class
        ],
        StockDepleted::class => [
            // e.g. SendStockDepletedAlert::class
        ],
    ];

    /**
     * External broker event → listener mapping.
     * These are keyed by the broker routing key / event name.
     */
    public static array $brokerListeners = [
        'product.created' => HandleProductCreated::class,
        'product.deleted' => HandleProductDeleted::class,
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

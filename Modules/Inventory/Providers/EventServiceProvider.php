<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Inventory\Events\LowStockAlert;
use Modules\Inventory\Events\StockLevelChanged;
use Modules\Inventory\Events\StockMovementRecorded;
use Modules\Inventory\Listeners\StockLevelAlertListener;
use Modules\Inventory\Listeners\UpdateAccountingValueListener;

/**
 * Inventory Event Service Provider
 *
 * Registers event listeners for the Inventory module.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        StockLevelChanged::class => [
            // Add listeners here when needed
        ],
        LowStockAlert::class => [
            StockLevelAlertListener::class,
        ],
        StockMovementRecorded::class => [
            UpdateAccountingValueListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

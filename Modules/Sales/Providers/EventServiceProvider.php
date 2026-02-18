<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Sales\Events\SalesOrderConfirmed;
use Modules\Sales\Listeners\CreateAccountingEntryListener;
use Modules\Sales\Listeners\LogSalesOrderConfirmation;
use Modules\Sales\Listeners\ReserveStockListener;

/**
 * Sales Event Service Provider
 *
 * Registers event listeners for the Sales module.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        SalesOrderConfirmed::class => [
            LogSalesOrderConfirmation::class,
            CreateAccountingEntryListener::class,
            ReserveStockListener::class,
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

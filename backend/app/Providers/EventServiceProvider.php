<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Inventory\Events\ProductCreated;
use App\Domain\Inventory\Events\ProductUpdated;
use App\Domain\Inventory\Events\StockAdjusted;
use App\Domain\Order\Events\OrderCancelled;
use App\Domain\Order\Events\OrderCompleted;
use App\Domain\Order\Events\OrderCreated;
use App\Domain\Tenant\Events\TenantCreated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

/**
 * Event service provider — maps domain events to listeners.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Event → Listener mappings.
     *
     * @var array<class-string, array<class-string>>
     */
    protected $listen = [
        ProductCreated::class => [
            // \App\Listeners\Inventory\SendLowStockAlert::class,
            // \App\Listeners\Inventory\SyncProductToSearch::class,
        ],
        ProductUpdated::class => [
            // \App\Listeners\Inventory\InvalidateProductCache::class,
        ],
        StockAdjusted::class => [
            // \App\Listeners\Inventory\CheckLowStockThreshold::class,
        ],
        OrderCreated::class => [
            // \App\Listeners\Order\SendOrderConfirmationEmail::class,
            // \App\Listeners\Order\NotifyWarehouse::class,
        ],
        OrderCancelled::class => [
            // \App\Listeners\Order\SendOrderCancellationEmail::class,
        ],
        OrderCompleted::class => [
            // \App\Listeners\Order\UpdateCustomerStats::class,
        ],
        TenantCreated::class => [
            // \App\Listeners\Tenant\ProvisionDefaultRoles::class,
            // \App\Listeners\Tenant\SendWelcomeEmail::class,
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

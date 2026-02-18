<?php

declare(strict_types=1);

namespace Modules\Procurement\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Procurement\Events\GoodsReceived;
use Modules\Procurement\Events\PurchaseOrderCreated;
use Modules\Procurement\Events\RequisitionApproved;
use Modules\Procurement\Events\SupplierRated;
use Modules\Procurement\Listeners\CreateAPInvoiceListener;
use Modules\Procurement\Listeners\UpdateStockOnReceiptListener;

/**
 * Procurement Event Service Provider
 *
 * Registers event listeners for the Procurement module.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        GoodsReceived::class => [
            UpdateStockOnReceiptListener::class,
            CreateAPInvoiceListener::class,
        ],
        PurchaseOrderCreated::class => [
            // Add listeners here when needed
        ],
        RequisitionApproved::class => [
            // Add listeners here when needed
        ],
        SupplierRated::class => [
            // Add listeners here when needed
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

<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Event Service Provider
 * 
 * Registers all event-listener mappings for the application.
 * Implements event-driven architecture for asynchronous workflows.
 * All events and listeners are tenant-aware and queued for performance.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Product Events
        \App\Events\Product\ProductCreated::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\Product\ProductUpdated::class => [
            \App\Listeners\Common\LogActivityListener::class,
        ],
        \App\Events\Product\ProductDeleted::class => [
            \App\Listeners\Common\LogActivityListener::class,
        ],
        \App\Events\Product\ProductPriceChanged::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],

        // Inventory Events
        \App\Events\Inventory\StockMovementRecorded::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],
        \App\Events\Inventory\StockIn::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],
        \App\Events\Inventory\StockOut::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],
        \App\Events\Inventory\StockTransfer::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],
        \App\Events\Inventory\StockAdjustment::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],
        \App\Events\Inventory\LowStockDetected::class => [
            \App\Listeners\Inventory\SendLowStockNotification::class,
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\Inventory\StockExpiring::class => [
            \App\Listeners\Inventory\SendStockExpiryAlert::class,
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],

        // Sales Events
        \App\Events\Sales\OrderCreated::class => [
            \App\Listeners\CRM\UpdateCustomerStatistics::class,
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],
        \App\Events\Sales\OrderApproved::class => [
            \App\Listeners\Sales\GenerateInvoiceFromOrder::class,
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\Sales\OrderFulfilled::class => [
            \App\Listeners\Sales\UpdateInventoryOnSale::class,
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],
        \App\Events\Sales\OrderShipped::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\Sales\OrderCancelled::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],

        // Invoice Events
        \App\Events\Invoice\InvoiceGenerated::class => [
            \App\Listeners\Invoice\SendInvoiceToCustomer::class,
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\Invoice\InvoiceApproved::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\Invoice\InvoicePaid::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],
        \App\Events\Invoice\InvoicePaymentReceived::class => [
            \App\Listeners\Invoice\SendPaymentConfirmation::class,
            \App\Listeners\CRM\UpdateCustomerStatistics::class,
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],
        \App\Events\Invoice\InvoiceOverdue::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],

        // Payment Events
        \App\Events\Payment\PaymentReceived::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],
        \App\Events\Payment\PaymentFailed::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],

        // CRM Events
        \App\Events\CRM\LeadCreated::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\CRM\CustomerCreated::class => [
            \App\Listeners\CRM\UpdateCustomerStatistics::class,
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\CRM\LeadConverted::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],

        // Procurement Events
        \App\Events\Procurement\PurchaseOrderCreated::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\Procurement\PurchaseOrderApproved::class => [
            \App\Listeners\Procurement\NotifyPurchaseOrderApproval::class,
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\Procurement\GoodsReceived::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],

        // Manufacturing Events
        \App\Events\Manufacturing\WorkOrderStarted::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\Manufacturing\WorkOrderCompleted::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
            \App\Listeners\Common\RecalculateMetricsListener::class,
        ],

        // Warehouse Events
        \App\Events\Warehouse\TransferInitiated::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\Warehouse\PickingCompleted::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
        ],
        \App\Events\Warehouse\PutawayCompleted::class => [
            \App\Listeners\Common\LogActivityListener::class,
            \App\Listeners\Common\SendNotificationListener::class,
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

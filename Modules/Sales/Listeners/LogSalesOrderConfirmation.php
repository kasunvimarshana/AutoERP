<?php

declare(strict_types=1);

namespace Modules\Sales\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Events\SalesOrderConfirmed;

/**
 * Sales Order Confirmed Listener
 *
 * Handles actions when a sales order is confirmed.
 * This listener logs the confirmation and can be extended to trigger
 * additional actions like sending notifications, updating inventory, etc.
 */
class LogSalesOrderConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SalesOrderConfirmed $event): void
    {
        Log::info('Sales order confirmed', [
            'order_id' => $event->salesOrder->id,
            'order_number' => $event->salesOrder->order_number,
            'customer_id' => $event->salesOrder->customer_id,
            'total_amount' => $event->salesOrder->total_amount,
            'currency' => $event->salesOrder->currency,
        ]);

        // Additional actions can be added here:
        // - Send confirmation email to customer
        // - Reserve inventory
        // - Create accounting entries
        // - Notify sales team
        // - Update CRM pipeline
    }
}

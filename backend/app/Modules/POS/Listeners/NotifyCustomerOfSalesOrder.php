<?php

namespace App\Modules\POS\Listeners;

use App\Modules\POS\Events\SalesOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerOfSalesOrder implements ShouldQueue
{
    public function handle(SalesOrderCreated $event): void
    {
        // Send notification to customer about new sales order
        // This would integrate with the notification system

        \Log::info('Sales Order Created Notification', [
            'order_number' => $event->salesOrder->order_number,
            'customer_id' => $event->salesOrder->customer_id,
            'total_amount' => $event->salesOrder->total_amount,
        ]);

        // Future: Send email, SMS, or push notification
        // Future: Create notification record in database
    }
}

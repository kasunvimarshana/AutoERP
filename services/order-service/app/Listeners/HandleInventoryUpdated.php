<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use Illuminate\Support\Facades\Log;

class HandleInventoryUpdated
{
    public function handle(OrderStatusChanged $event): void
    {
        Log::info('HandleInventoryUpdated: processing', [
            'order_id'   => $event->orderId,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
        ]);
        // Update any pending orders if inventory was restocked or depleted
    }
}

<?php

namespace App\Modules\Manufacturing\Listeners;

use App\Modules\Manufacturing\Events\ProductionOrderCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Notify On Production Order Completion Listener
 *
 * Sends notifications when a production order is completed (async).
 */
class NotifyOnProductionOrderCompletion implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ProductionOrderCompleted $event): void
    {
        $productionOrder = $event->productionOrder;

        Log::info('Sending notifications for completed production order', [
            'production_order_id' => $productionOrder->id,
            'production_order_number' => $productionOrder->production_order_number,
        ]);

        // TODO: Implement notification logic
        // Example: Send email to production manager
        // Example: Send in-app notification to relevant users
        // Example: Update dashboard metrics

        // Load the creator if exists
        if ($productionOrder->created_by && $productionOrder->creator) {
            // Notification::send($productionOrder->creator, new ProductionOrderCompletedNotification($productionOrder));
        }

        Log::info('Production order completion notifications sent', [
            'production_order_id' => $productionOrder->id,
        ]);
    }
}

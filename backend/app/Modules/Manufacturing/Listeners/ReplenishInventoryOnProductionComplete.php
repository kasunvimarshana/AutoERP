<?php

namespace App\Modules\Manufacturing\Listeners;

use App\Modules\Manufacturing\Events\ProductionOrderCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Replenish Inventory On Production Complete Listener
 *
 * Handles inventory replenishment when production is completed (async).
 * Note: Actual replenishment is handled synchronously in the service,
 * this is for any additional async processing if needed.
 */
class ReplenishInventoryOnProductionComplete implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ProductionOrderCompleted $event): void
    {
        $productionOrder = $event->productionOrder;

        Log::info('Production order completed, finished goods added to inventory', [
            'production_order_id' => $productionOrder->id,
            'production_order_number' => $productionOrder->production_order_number,
            'product_id' => $productionOrder->product_id,
            'quantity' => $productionOrder->quantity,
        ]);

        // Any additional async processing can be added here
        // The actual inventory replenishment is handled synchronously in the service
    }
}

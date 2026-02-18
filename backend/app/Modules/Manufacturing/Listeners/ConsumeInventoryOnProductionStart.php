<?php

namespace App\Modules\Manufacturing\Listeners;

use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\Manufacturing\Events\ProductionOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Consume Inventory On Production Start Listener
 *
 * Handles material consumption when production starts (async).
 * Note: Actual consumption is handled synchronously in the service,
 * this is for any additional async processing if needed.
 */
class ConsumeInventoryOnProductionStart implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected StockMovementService $stockMovementService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ProductionOrderCreated $event): void
    {
        $productionOrder = $event->productionOrder;

        Log::info('Production order created, preparing for material consumption', [
            'production_order_id' => $productionOrder->id,
            'production_order_number' => $productionOrder->production_order_number,
        ]);

        // Any additional async processing can be added here
        // The actual material consumption is handled synchronously in the service
    }
}

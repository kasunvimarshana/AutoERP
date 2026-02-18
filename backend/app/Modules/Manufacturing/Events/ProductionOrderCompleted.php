<?php

namespace App\Modules\Manufacturing\Events;

use App\Modules\Manufacturing\Models\ProductionOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Production Order Completed Event
 *
 * Dispatched when a production order is completed.
 */
class ProductionOrderCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ProductionOrder $productionOrder
    ) {}
}

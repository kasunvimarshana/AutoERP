<?php

namespace App\Modules\Manufacturing\Events;

use App\Modules\Manufacturing\Models\ProductionOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Production Order Created Event
 *
 * Dispatched when a new production order is created.
 */
class ProductionOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ProductionOrder $productionOrder
    ) {}
}

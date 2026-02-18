<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Entities\StockLevel;

/**
 * Low Stock Alert Event
 *
 * Dispatched when a product's stock level falls below reorder point.
 */
class LowStockAlert
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public StockLevel $stockLevel
    ) {}
}

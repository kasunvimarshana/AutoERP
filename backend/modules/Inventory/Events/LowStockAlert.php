<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Models\Product;

/**
 * Low Stock Alert Event
 *
 * Fired when stock falls below reorder level.
 */
class LowStockAlert
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Product $product,
        public string $warehouseId,
        public float $currentStock
    ) {}
}

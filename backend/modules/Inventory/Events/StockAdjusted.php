<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Models\StockLedger;

/**
 * Stock Adjusted Event
 *
 * Fired when stock is adjusted.
 */
class StockAdjusted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public StockLedger $stockLedger
    ) {}
}

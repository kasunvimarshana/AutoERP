<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Entities\StockLevel;

/**
 * Stock Level Changed Event
 *
 * Dispatched when a stock level is modified.
 */
class StockLevelChanged
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public StockLevel $stockLevel
    ) {}
}

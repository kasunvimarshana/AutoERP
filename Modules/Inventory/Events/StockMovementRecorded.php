<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Entities\StockMovement;

/**
 * Stock Movement Recorded Event
 *
 * Dispatched when a stock movement is recorded.
 */
class StockMovementRecorded
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public StockMovement $stockMovement
    ) {}
}

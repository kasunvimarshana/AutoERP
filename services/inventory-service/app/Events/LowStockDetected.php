<?php

namespace App\Events;

use App\Models\InventoryItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly InventoryItem $item,
        public readonly int           $availableQuantity,
        public readonly int           $reorderPoint,
    ) {}
}

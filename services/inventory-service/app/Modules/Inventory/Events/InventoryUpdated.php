<?php

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly InventoryItem $inventoryItem,
        public readonly string $action,
        public readonly array $context = [],
    ) {}
}

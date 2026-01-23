<?php

namespace App\Modules\InventoryManagement\Events;

use App\Modules\InventoryManagement\Models\InventoryItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockLevelLow
{
    use Dispatchable, SerializesModels;

    public InventoryItem $inventoryItem;

    public function __construct(InventoryItem $inventoryItem)
    {
        $this->inventoryItem = $inventoryItem;
    }
}

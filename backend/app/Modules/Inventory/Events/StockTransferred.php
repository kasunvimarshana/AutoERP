<?php

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\Inventory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockTransferred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Inventory $fromInventory,
        public Inventory $toInventory,
        public int $quantity
    ) {
    }
}

<?php

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Models\StockLedger;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockAdjusted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Inventory $inventory,
        public StockLedger $ledgerEntry
    ) {
    }
}

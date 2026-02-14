<?php

namespace App\Events;

use App\Modules\Inventory\Models\Stock;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockAdjusted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Stock $stock,
        public string $type,
        public float $quantity,
        public ?string $reason = null
    ) {}
}

<?php

namespace App\Events;

use App\Models\InventoryItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockReserved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly InventoryItem $item,
        public readonly int           $reservedQuantity,
        public readonly string        $reason,
        public readonly ?string       $referenceType = null,
        public readonly ?string       $referenceId   = null,
        public readonly ?int          $performedBy   = null,
    ) {}
}

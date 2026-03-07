<?php

namespace App\Events;

use App\Models\InventoryItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly InventoryItem $item,
        public readonly array         $changes,
        public readonly ?int          $performedBy = null,
    ) {}
}

<?php

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\Inventory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Inventory $inventory,
        public readonly int $delta,
        public readonly string $reason,
    ) {}
}

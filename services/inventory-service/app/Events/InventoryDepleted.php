<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Inventory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryDepleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Inventory $inventory) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('inventory'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'inventory.depleted';
    }

    public function broadcastWith(): array
    {
        return [
            'event'              => 'inventory.depleted',
            'inventory_id'       => $this->inventory->id,
            'product_id'         => $this->inventory->product_id,
            'quantity'           => $this->inventory->quantity,
            'warehouse_location' => $this->inventory->warehouse_location,
            'timestamp'          => now()->toIso8601String(),
        ];
    }
}

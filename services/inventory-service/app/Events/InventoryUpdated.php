<?php

namespace App\Events;

use App\Models\Inventory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Inventory $inventory,
        public readonly string    $movementType,
        public readonly int       $quantityChanged,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->inventory->tenant_id),
            new PrivateChannel('inventory.' . $this->inventory->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'inventory.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'inventory_id'      => $this->inventory->id,
            'tenant_id'         => $this->inventory->tenant_id,
            'product_id'        => $this->inventory->product_id,
            'warehouse_id'      => $this->inventory->warehouse_id,
            'quantity'          => $this->inventory->quantity,
            'reserved_quantity' => $this->inventory->reserved_quantity,
            'available_quantity'=> $this->inventory->available_quantity,
            'movement_type'     => $this->movementType,
            'quantity_changed'  => $this->quantityChanged,
            'timestamp'         => now()->toIso8601String(),
        ];
    }
}

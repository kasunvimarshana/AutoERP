<?php

namespace App\Events;

use App\Models\Inventory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockLow implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Inventory $inventory,
        public readonly int       $threshold,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->inventory->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'inventory.stock_low';
    }

    public function broadcastWith(): array
    {
        return [
            'inventory_id'      => $this->inventory->id,
            'tenant_id'         => $this->inventory->tenant_id,
            'product_id'        => $this->inventory->product_id,
            'warehouse_id'      => $this->inventory->warehouse_id,
            'available_quantity'=> $this->inventory->available_quantity,
            'threshold'         => $this->threshold,
            'timestamp'         => now()->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Inventory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Inventory $inventory,
        public readonly string $transactionType,
        public readonly int $quantityChange,
        public readonly array $previousData = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('inventory'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'inventory.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'event'            => 'inventory.updated',
            'inventory_id'     => $this->inventory->id,
            'product_id'       => $this->inventory->product_id,
            'transaction_type' => $this->transactionType,
            'quantity_change'  => $this->quantityChange,
            'inventory'        => [
                'id'                 => $this->inventory->id,
                'product_id'         => $this->inventory->product_id,
                'quantity'           => $this->inventory->quantity,
                'reserved_quantity'  => $this->inventory->reserved_quantity,
                'available_quantity' => $this->inventory->available_quantity,
                'warehouse_location' => $this->inventory->warehouse_location,
                'reorder_level'      => $this->inventory->reorder_level,
                'status'             => $this->inventory->status,
            ],
            'previous_data' => $this->previousData,
            'timestamp'     => now()->toIso8601String(),
        ];
    }
}

<?php

namespace App\Listeners\Inventory;

use App\Events\InventoryUpdated;
use App\Services\MessageBrokerService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleInventoryUpdated implements ShouldQueue
{
    public function __construct(private readonly MessageBrokerService $broker) {}

    public function handle(InventoryUpdated $event): void
    {
        $this->broker->publish('inventory.updated', [
            'inventory_id' => $event->inventory->id,
            'tenant_id' => $event->inventory->tenant_id,
            'product_id' => $event->inventory->product_id,
            'warehouse_id' => $event->inventory->warehouse_id,
            'quantity' => $event->inventory->quantity,
            'reserved_quantity' => $event->inventory->reserved_quantity,
            'available_quantity' => $event->inventory->available_quantity,
            'action' => $event->action,
            'metadata' => $event->metadata,
        ]);
    }
}

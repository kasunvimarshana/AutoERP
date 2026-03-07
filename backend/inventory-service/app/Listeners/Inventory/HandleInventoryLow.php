<?php

namespace App\Listeners\Inventory;

use App\Events\InventoryLow;
use App\Services\MessageBrokerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleInventoryLow implements ShouldQueue
{
    public function __construct(private readonly MessageBrokerService $broker) {}

    public function handle(InventoryLow $event): void
    {
        Log::warning('LOW INVENTORY ALERT', [
            'inventory_id' => $event->inventory->id,
            'product_id' => $event->inventory->product_id,
            'available' => $event->inventory->available_quantity,
            'reorder_level' => $event->inventory->reorder_level,
        ]);

        $this->broker->publish('inventory.low', [
            'inventory_id' => $event->inventory->id,
            'tenant_id' => $event->inventory->tenant_id,
            'product_id' => $event->inventory->product_id,
            'warehouse_id' => $event->inventory->warehouse_id,
            'available_quantity' => $event->inventory->available_quantity,
            'reorder_level' => $event->inventory->reorder_level,
        ]);
    }
}

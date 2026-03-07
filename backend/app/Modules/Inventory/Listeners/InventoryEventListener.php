<?php

namespace App\Modules\Inventory\Listeners;

use App\Modules\Inventory\Events\InventoryUpdated;
use App\Services\MessageBroker\MessageBrokerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InventoryEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public function __construct(
        private readonly MessageBrokerInterface $messageBroker,
    ) {}

    public function handle(InventoryUpdated $event): void
    {
        $inventory = $event->inventory;

        try {
            $this->messageBroker->publish('inventory.exchange', 'inventory.updated', [
                'inventory_id'  => $inventory->id,
                'product_id'    => $inventory->product_id,
                'tenant_id'     => $inventory->tenant_id,
                'quantity'      => $inventory->quantity,
                'delta'         => $event->delta,
                'reason'        => $event->reason,
                'timestamp'     => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to publish inventory.updated event', ['error' => $e->getMessage()]);
        }

        if ($inventory->quantity <= $inventory->reorder_point) {
            Log::warning('Low stock alert', [
                'inventory_id'   => $inventory->id,
                'product_id'     => $inventory->product_id,
                'tenant_id'      => $inventory->tenant_id,
                'quantity'       => $inventory->quantity,
                'reorder_point'  => $inventory->reorder_point,
                'reorder_qty'    => $inventory->reorder_quantity,
            ]);
        }
    }
}

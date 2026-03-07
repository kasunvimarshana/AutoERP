<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use App\Webhooks\WebhookDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyLowStock implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries   = 3;
    public int $backoff = 10;

    public function __construct(
        private readonly WebhookDispatcher $webhookDispatcher,
    ) {}

    public function handle(LowStockDetected $event): void
    {
        Log::warning('Low stock detected', [
            'tenant_id'          => $event->item->tenant_id,
            'inventory_item_id'  => $event->item->id,
            'product_id'         => $event->item->product_id,
            'available_quantity' => $event->availableQuantity,
            'reorder_point'      => $event->reorderPoint,
        ]);

        $this->webhookDispatcher->dispatch(
            tenantId:  $event->item->tenant_id,
            eventName: 'stock.low',
            data: [
                'inventory_item_id'  => $event->item->id,
                'product_id'         => $event->item->product_id,
                'warehouse_id'       => $event->item->warehouse_id,
                'sku'                => $event->item->sku,
                'available_quantity' => $event->availableQuantity,
                'reorder_point'      => $event->reorderPoint,
                'reorder_quantity'   => $event->item->reorder_quantity,
            ],
        );
    }
}

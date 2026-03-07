<?php

namespace App\Listeners;

use App\Events\InventoryUpdatedEvent;
use App\Models\Inventory;
use App\Outbox\OutboxPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class InventoryUpdatedListener implements ShouldQueue
{
    public string $queue = 'events';

    public function __construct(
        private readonly OutboxPublisher $outboxPublisher
    ) {
    }

    public function handle(InventoryUpdatedEvent $event): void
    {
        // Persist the event to the outbox for reliable, at-least-once delivery.
        // The OutboxProcessor will forward it to the message broker and webhooks.
        $this->outboxPublisher->store($event, Inventory::class, $event->inventory->id);

        // Also queue a low-stock outbox entry when inventory drops below threshold.
        $threshold = $event->inventory->product->low_stock_threshold ?? 10;
        if ($event->inventory->quantity <= $threshold) {
            $this->outboxPublisher->storeLowStock($event->inventory, $event->tenantId);
        }

        Log::info("[InventoryUpdatedListener] Queued inventory.updated event in outbox for product #{$event->inventory->product_id}", [
            'action'    => $event->action,
            'qty_delta' => $event->quantityChanged,
            'tenant_id' => $event->tenantId,
        ]);
    }

    public function failed(InventoryUpdatedEvent $event, \Throwable $exception): void
    {
        Log::error("[InventoryUpdatedListener] Failed: " . $exception->getMessage());
    }
}

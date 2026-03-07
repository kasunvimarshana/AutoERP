<?php

namespace App\Listeners;

use App\Events\InventoryUpdatedEvent;
use App\MessageBroker\Contracts\MessageBrokerInterface;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class InventoryUpdatedListener implements ShouldQueue
{
    public string $queue = 'events';

    public function __construct(
        private readonly MessageBrokerInterface $broker,
        private readonly WebhookService $webhookService
    ) {
    }

    public function handle(InventoryUpdatedEvent $event): void
    {
        // Publish to message broker
        $this->broker->publish('inventory.updated', $event->toPayload());

        // Trigger low-stock webhooks if applicable
        if ($event->inventory->quantity <= ($event->inventory->product->low_stock_threshold ?? 10)) {
            $this->webhookService->dispatchWebhook(
                'inventory.low_stock',
                $event->toPayload(),
                $event->tenantId
            );
        }

        // Trigger webhooks for subscribed endpoints
        $this->webhookService->dispatchWebhook(
            'inventory.updated',
            $event->toPayload(),
            $event->tenantId
        );

        Log::info("[InventoryUpdatedListener] Inventory updated for product #{$event->inventory->product_id}", [
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

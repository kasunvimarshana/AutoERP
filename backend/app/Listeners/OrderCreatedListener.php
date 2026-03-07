<?php

namespace App\Listeners;

use App\Events\OrderCreatedEvent;
use App\Models\Order;
use App\Outbox\OutboxPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class OrderCreatedListener implements ShouldQueue
{
    public string $queue = 'events';

    public function __construct(
        private readonly OutboxPublisher $outboxPublisher
    ) {
    }

    public function handle(OrderCreatedEvent $event): void
    {
        // Persist the event to the outbox for reliable, at-least-once delivery.
        // The OutboxProcessor will forward it to the message broker and webhooks.
        $this->outboxPublisher->store($event, Order::class, $event->order->id);

        Log::info("[OrderCreatedListener] Queued order.created event in outbox for order #{$event->order->order_number}", [
            'order_id'  => $event->order->id,
            'tenant_id' => $event->tenantId,
        ]);
    }

    public function failed(OrderCreatedEvent $event, \Throwable $exception): void
    {
        Log::error("[OrderCreatedListener] Failed processing order.created event: " . $exception->getMessage(), [
            'order_id' => $event->order->id,
        ]);
    }
}

<?php

namespace App\Listeners;

use App\Events\OrderStatusChangedEvent;
use App\Models\Order;
use App\Outbox\OutboxPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class OrderStatusChangedListener implements ShouldQueue
{
    public string $queue = 'events';

    public function __construct(
        private readonly OutboxPublisher $outboxPublisher
    ) {
    }

    public function handle(OrderStatusChangedEvent $event): void
    {
        $this->outboxPublisher->store($event, Order::class, $event->order->id);

        Log::info("[OrderStatusChangedListener] Queued order.status_changed event in outbox for order #{$event->order->order_number}", [
            'order_id'        => $event->order->id,
            'previous_status' => $event->previousStatus,
            'new_status'      => $event->newStatus,
            'tenant_id'       => $event->tenantId,
        ]);
    }

    public function failed(OrderStatusChangedEvent $event, \Throwable $exception): void
    {
        Log::error("[OrderStatusChangedListener] Failed processing order.status_changed event: " . $exception->getMessage(), [
            'order_id' => $event->order->id,
        ]);
    }
}

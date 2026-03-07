<?php

namespace App\Listeners;

use App\Events\OrderCancelledEvent;
use App\Models\Order;
use App\Outbox\OutboxPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class OrderCancelledListener implements ShouldQueue
{
    public string $queue = 'events';

    public function __construct(
        private readonly OutboxPublisher $outboxPublisher
    ) {
    }

    public function handle(OrderCancelledEvent $event): void
    {
        $this->outboxPublisher->store($event, Order::class, $event->order->id);

        Log::info("[OrderCancelledListener] Queued order.cancelled event in outbox for order #{$event->order->order_number}", [
            'order_id'  => $event->order->id,
            'reason'    => $event->reason,
            'tenant_id' => $event->tenantId,
        ]);
    }

    public function failed(OrderCancelledEvent $event, \Throwable $exception): void
    {
        Log::error("[OrderCancelledListener] Failed processing order.cancelled event: " . $exception->getMessage(), [
            'order_id' => $event->order->id,
        ]);
    }
}

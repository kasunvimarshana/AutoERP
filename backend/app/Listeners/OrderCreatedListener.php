<?php

namespace App\Listeners;

use App\Events\OrderCreatedEvent;
use App\MessageBroker\Contracts\MessageBrokerInterface;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class OrderCreatedListener implements ShouldQueue
{
    public string $queue = 'events';

    public function __construct(
        private readonly MessageBrokerInterface $broker,
        private readonly WebhookService $webhookService
    ) {
    }

    public function handle(OrderCreatedEvent $event): void
    {
        // Publish to message broker for other services
        $this->broker->publish('order.created', $event->toPayload());

        // Trigger webhooks for subscribed endpoints
        $this->webhookService->dispatchWebhook(
            'order.created',
            $event->toPayload(),
            $event->tenantId
        );

        Log::info("[OrderCreatedListener] Order #{$event->order->order_number} processed.", [
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

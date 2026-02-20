<?php

namespace App\Listeners;

use App\Enums\WebhookDeliveryStatus;
use App\Events\InvoiceCreated;
use App\Events\OrderCreated;
use App\Events\PaymentRecorded;
use App\Events\ProductCreated;
use App\Events\StockAdjusted;
use App\Jobs\DeliverWebhookJob;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Events\Dispatcher;

class WebhookEventSubscriber
{
    private const EVENT_MAP = [
        OrderCreated::class => 'order.created',
        InvoiceCreated::class => 'invoice.created',
        PaymentRecorded::class => 'payment.recorded',
        ProductCreated::class => 'product.created',
        StockAdjusted::class => 'stock.adjusted',
    ];

    public function handle(object $event): void
    {
        $eventName = self::EVENT_MAP[$event::class] ?? null;

        if ($eventName === null) {
            return;
        }

        $tenantId = $this->resolveTenantId($event);

        if ($tenantId === null) {
            return;
        }

        $webhooks = Webhook::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Webhook $wh) => in_array($eventName, $wh->events ?? [], true));

        foreach ($webhooks as $webhook) {
            $delivery = WebhookDelivery::create([
                'tenant_id' => $tenantId,
                'webhook_id' => $webhook->id,
                'event_name' => $eventName,
                'payload' => method_exists($event, 'toArray') ? $event->toArray() : [],
                'status' => WebhookDeliveryStatus::Pending,
                'attempt_count' => 0,
            ]);

            DeliverWebhookJob::dispatch($delivery->id);
        }
    }

    private function resolveTenantId(object $event): ?string
    {
        if ($event instanceof OrderCreated) {
            return $event->order->tenant_id;
        }
        if ($event instanceof InvoiceCreated) {
            return $event->invoice->tenant_id;
        }
        if ($event instanceof PaymentRecorded) {
            return $event->payment->tenant_id;
        }
        if ($event instanceof ProductCreated) {
            return $event->product->tenant_id;
        }
        if ($event instanceof StockAdjusted) {
            return $event->movement->tenant_id;
        }

        return null;
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            OrderCreated::class => 'handle',
            InvoiceCreated::class => 'handle',
            PaymentRecorded::class => 'handle',
            ProductCreated::class => 'handle',
            StockAdjusted::class => 'handle',
        ];
    }
}

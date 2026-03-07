<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PublishOrderCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'order-events';

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(private readonly MessageBrokerInterface $broker) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        $payload = [
            'event'          => 'order.created',
            'order_id'       => $order->id,
            'order_number'   => $order->order_number,
            'tenant_id'      => $order->tenant_id,
            'customer_id'    => $order->customer_id,
            'customer_email' => $order->customer_email,
            'status'         => $order->status,
            'payment_status' => $order->payment_status,
            'total'          => $order->total,
            'currency'       => $order->currency,
            'items'          => $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'unit_price' => $item->unit_price,
            ])->toArray(),
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            $published = $this->broker->publish(
                topic:   config('services.topics.orders', 'orders.events'),
                payload: $payload,
            );

            if ($published) {
                Log::info('PublishOrderCreated: published to broker', [
                    'order_id'  => $order->id,
                    'tenant_id' => $order->tenant_id,
                ]);
            } else {
                Log::warning('PublishOrderCreated: broker returned false', [
                    'order_id' => $order->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('PublishOrderCreated error', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}

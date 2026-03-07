<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PublishOrderStatusChanged implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'order-events';

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(private readonly MessageBrokerInterface $broker) {}

    public function handle(OrderStatusChanged $event): void
    {
        $payload = [
            'event'      => 'order.status_changed',
            'order_id'   => $event->orderId,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'metadata'   => $event->metadata,
            'timestamp'  => now()->toIso8601String(),
        ];

        try {
            $published = $this->broker->publish(
                topic:   config('services.topics.orders', 'orders.events'),
                payload: $payload,
            );

            if ($published) {
                Log::info('PublishOrderStatusChanged: published to broker', [
                    'order_id'   => $event->orderId,
                    'new_status' => $event->newStatus,
                ]);
            } else {
                Log::warning('PublishOrderStatusChanged: broker returned false', [
                    'order_id' => $event->orderId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('PublishOrderStatusChanged error', [
                'order_id' => $event->orderId,
                'error'    => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}

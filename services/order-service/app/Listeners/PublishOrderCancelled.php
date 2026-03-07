<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PublishOrderCancelled implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'order-events';

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(private readonly MessageBrokerInterface $broker) {}

    public function handle(OrderCancelled $event): void
    {
        $payload = [
            'event'     => 'order.cancelled',
            'order_id'  => $event->orderId,
            'reason'    => $event->reason,
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            $published = $this->broker->publish(
                topic:   config('services.topics.orders', 'orders.events'),
                payload: $payload,
            );

            if ($published) {
                Log::info('PublishOrderCancelled: published to broker', [
                    'order_id' => $event->orderId,
                ]);
            } else {
                Log::warning('PublishOrderCancelled: broker returned false', [
                    'order_id' => $event->orderId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('PublishOrderCancelled error', [
                'order_id' => $event->orderId,
                'error'    => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}

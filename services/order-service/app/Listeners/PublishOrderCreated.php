<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class PublishOrderCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'order-events';

    public int $tries = 3;

    public int $backoff = 5;

    public function handle(OrderCreated $event): void
    {
        $this->publish('order.created', $event->broadcastWith());
    }

    public function failed(OrderCreated $event, Throwable $exception): void
    {
        Log::error('Failed to publish OrderCreated event to RabbitMQ', [
            'order_id' => $event->order->id,
            'error'    => $exception->getMessage(),
        ]);
    }

    private function publish(string $routingKey, array $payload): void
    {
        $config = config('rabbitmq');

        $connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost']
        );

        $channel = $connection->channel();

        $channel->exchange_declare(
            $config['exchange'],
            $config['exchange_type'],
            false,
            true,
            false
        );

        $message = new AMQPMessage(
            json_encode($payload, JSON_THROW_ON_ERROR),
            [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]
        );

        $channel->basic_publish($message, $config['exchange'], $routingKey);

        Log::info('Published event to RabbitMQ', [
            'routing_key' => $routingKey,
            'order_id'    => $payload['order_id'] ?? null,
        ]);

        $channel->close();
        $connection->close();
    }
}

<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InventoryUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class PublishInventoryUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'inventory-events';

    public int $tries = 3;

    public int $backoff = 5;

    public function handle(InventoryUpdated $event): void
    {
        $this->publish('inventory.updated', $event->broadcastWith());
    }

    public function failed(InventoryUpdated $event, Throwable $exception): void
    {
        Log::error('Failed to publish InventoryUpdated event to RabbitMQ', [
            'inventory_id' => $event->inventory->id,
            'product_id'   => $event->inventory->product_id,
            'error'        => $exception->getMessage(),
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
            'payload'     => $payload,
        ]);

        $channel->close();
        $connection->close();
    }
}

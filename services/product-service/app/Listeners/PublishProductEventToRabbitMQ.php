<?php

namespace App\Listeners;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PublishProductEventToRabbitMQ implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 5;

    public function handle(ProductCreated|ProductUpdated|ProductDeleted $event): void
    {
        $routingKey = match (true) {
            $event instanceof ProductCreated => 'product.created',
            $event instanceof ProductUpdated => 'product.updated',
            $event instanceof ProductDeleted => 'product.deleted',
        };

        $payload = $this->buildPayload($event, $routingKey);

        $this->publish($routingKey, $payload);
    }

    private function buildPayload(ProductCreated|ProductUpdated|ProductDeleted $event, string $routingKey): array
    {
        $base = [
            'event'      => $routingKey,
            'tenant_id'  => $event->tenantId,
            'timestamp'  => now()->toIso8601String(),
            'service'    => 'product-service',
        ];

        return match (true) {
            $event instanceof ProductCreated => array_merge($base, [
                'data' => [
                    'product_id' => $event->product->id,
                    'sku'        => $event->product->sku,
                    'name'       => $event->product->name,
                    'price'      => $event->product->price,
                    'status'     => $event->product->status,
                    'triggered_by' => $event->triggeredBy,
                ],
            ]),
            $event instanceof ProductUpdated => array_merge($base, [
                'data' => [
                    'product_id' => $event->product->id,
                    'sku'        => $event->product->sku,
                    'changes'    => $event->changes,
                    'triggered_by' => $event->triggeredBy,
                ],
            ]),
            $event instanceof ProductDeleted => array_merge($base, [
                'data' => [
                    'product_id' => $event->productId,
                    'sku'        => $event->sku,
                    'triggered_by' => $event->triggeredBy,
                ],
            ]),
        };
    }

    private function publish(string $routingKey, array $payload): void
    {
        $config = config('rabbitmq');

        $connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost'],
            false,
            'AMQPLAIN',
            null,
            'en_US',
            (float) $config['connection_timeout'],
            (float) $config['read_write_timeout'],
            null,
            (bool) $config['keepalive'],
            (int) $config['heartbeat'],
        );

        $channel = $connection->channel();

        $channel->exchange_declare(
            $config['exchange']['name'],
            $config['exchange']['type'],
            $config['exchange']['passive'],
            $config['exchange']['durable'],
            $config['exchange']['auto_delete'],
        );

        $message = new AMQPMessage(
            json_encode($payload, JSON_THROW_ON_ERROR),
            [
                'delivery_mode'  => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type'   => 'application/json',
                'timestamp'      => time(),
                'message_id'     => (string) \Illuminate\Support\Str::uuid(),
            ]
        );

        $channel->basic_publish($message, $config['exchange']['name'], $routingKey);

        $channel->close();
        $connection->close();

        Log::info('Product event published to RabbitMQ', [
            'routing_key' => $routingKey,
            'tenant_id'   => $payload['tenant_id'],
        ]);
    }

    public function failed(ProductCreated|ProductUpdated|ProductDeleted $event, \Throwable $exception): void
    {
        Log::error('Failed to publish product event to RabbitMQ', [
            'event'     => $event::class,
            'exception' => $exception->getMessage(),
        ]);
    }
}

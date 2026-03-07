<?php

namespace App\Listeners;

use App\Events\InventoryUpdated;
use App\Events\LowStockDetected;
use App\Events\StockReleased;
use App\Events\StockReserved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PublishInventoryEventToRabbitMQ implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue   = 'rabbitmq-publisher';
    public int    $tries   = 5;
    public int    $backoff = 5;

    public function handle(
        InventoryUpdated|StockReserved|StockReleased|LowStockDetected $event
    ): void {
        $payload    = $this->buildPayload($event);
        $routingKey = $this->resolveRoutingKey($event);

        $this->publish($routingKey, $payload);
    }

    private function buildPayload(
        InventoryUpdated|StockReserved|StockReleased|LowStockDetected $event
    ): array {
        return match (true) {
            $event instanceof InventoryUpdated => [
                'event'     => 'inventory.updated',
                'tenant_id' => $event->item->tenant_id,
                'data'      => [
                    'inventory_item_id' => $event->item->id,
                    'product_id'        => $event->item->product_id,
                    'warehouse_id'      => $event->item->warehouse_id,
                    'quantity'          => $event->item->quantity,
                    'reserved_quantity' => $event->item->reserved_quantity,
                    'changes'           => $event->changes,
                    'performed_by'      => $event->performedBy,
                ],
                'timestamp' => now()->toIso8601String(),
            ],

            $event instanceof StockReserved => [
                'event'     => 'stock.reserved',
                'tenant_id' => $event->item->tenant_id,
                'data'      => [
                    'inventory_item_id' => $event->item->id,
                    'product_id'        => $event->item->product_id,
                    'warehouse_id'      => $event->item->warehouse_id,
                    'reserved_quantity' => $event->reservedQuantity,
                    'reason'            => $event->reason,
                    'reference_type'    => $event->referenceType,
                    'reference_id'      => $event->referenceId,
                ],
                'timestamp' => now()->toIso8601String(),
            ],

            $event instanceof StockReleased => [
                'event'     => 'stock.released',
                'tenant_id' => $event->item->tenant_id,
                'data'      => [
                    'inventory_item_id'  => $event->item->id,
                    'product_id'         => $event->item->product_id,
                    'warehouse_id'       => $event->item->warehouse_id,
                    'released_quantity'  => $event->releasedQuantity,
                    'reason'             => $event->reason,
                    'reference_type'     => $event->referenceType,
                    'reference_id'       => $event->referenceId,
                ],
                'timestamp' => now()->toIso8601String(),
            ],

            $event instanceof LowStockDetected => [
                'event'     => 'stock.low',
                'tenant_id' => $event->item->tenant_id,
                'data'      => [
                    'inventory_item_id'  => $event->item->id,
                    'product_id'         => $event->item->product_id,
                    'warehouse_id'       => $event->item->warehouse_id,
                    'available_quantity' => $event->availableQuantity,
                    'reorder_point'      => $event->reorderPoint,
                    'reorder_quantity'   => $event->item->reorder_quantity,
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        };
    }

    private function resolveRoutingKey(
        InventoryUpdated|StockReserved|StockReleased|LowStockDetected $event
    ): string {
        return match (true) {
            $event instanceof InventoryUpdated => config('rabbitmq.routing_keys.inventory.updated', 'inventory.updated'),
            $event instanceof StockReserved    => config('rabbitmq.routing_keys.stock.reserved', 'stock.reserved'),
            $event instanceof StockReleased    => config('rabbitmq.routing_keys.stock.released', 'stock.released'),
            $event instanceof LowStockDetected => config('rabbitmq.routing_keys.stock.low', 'stock.low'),
        };
    }

    private function publish(string $routingKey, array $payload): void
    {
        $cfg = config('rabbitmq');

        try {
            $connection = new AMQPStreamConnection(
                $cfg['host'],
                $cfg['port'],
                $cfg['user'],
                $cfg['password'],
                $cfg['vhost'],
                false,
                'AMQPLAIN',
                null,
                'en_US',
                (float) $cfg['connection_timeout'],
                (float) $cfg['read_write_timeout'],
                null,
                (bool)  $cfg['keepalive'],
                (int)   $cfg['heartbeat'],
            );

            $channel = $connection->channel();

            $channel->exchange_declare(
                $cfg['exchange']['name'],
                $cfg['exchange']['type'],
                $cfg['exchange']['passive'],
                $cfg['exchange']['durable'],
                $cfg['exchange']['auto_delete'],
            );

            $message = new AMQPMessage(
                json_encode($payload, JSON_THROW_ON_ERROR),
                [
                    'content_type'  => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                ]
            );

            $channel->basic_publish($message, $cfg['exchange']['name'], $routingKey);

            $channel->close();
            $connection->close();

            Log::info('Inventory event published to RabbitMQ', [
                'routing_key' => $routingKey,
                'event'       => $payload['event'],
                'tenant_id'   => $payload['tenant_id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to publish inventory event to RabbitMQ', [
                'routing_key' => $routingKey,
                'error'       => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Messaging\RabbitMQConsumer;
use App\Messaging\RabbitMQPublisher;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;

class InventoryEventConsumer extends Command
{
    protected $signature   = 'inventory:consume';
    protected $description = 'Consume inventory commands from RabbitMQ';

    public function __construct(
        private readonly InventoryService  $inventoryService,
        private readonly RabbitMQPublisher $publisher,
        private readonly RabbitMQConsumer  $consumer
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $reserveQueue = config('rabbitmq.queues.reserve_inventory', 'reserve-inventory');
        $releaseQueue = config('rabbitmq.queues.release_inventory', 'release-inventory');

        $this->info("[InventoryConsumer] Listening on: {$reserveQueue}, {$releaseQueue}");
        Log::info('[InventoryConsumer] Starting event consumer');

        $channel = $this->consumer->getChannel();

        // Declare both queues
        $channel->queue_declare($reserveQueue, false, true, false, false);
        $channel->queue_declare($releaseQueue, false, true, false, false);
        $channel->basic_qos(0, 1, false);

        // Consumer for reserve-inventory
        $channel->basic_consume(
            $reserveQueue,
            '',
            false,
            false,
            false,
            false,
            function ($msg) use ($reserveQueue): void {
                $this->handleMessage($msg, 'RESERVE');
            }
        );

        // Consumer for release-inventory
        $channel->basic_consume(
            $releaseQueue,
            '',
            false,
            false,
            false,
            false,
            function ($msg): void {
                $this->handleMessage($msg, 'RELEASE');
            }
        );

        Log::info('[InventoryConsumer] Waiting for messages');

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        return self::SUCCESS;
    }

    private function handleMessage(\PhpAmqpLib\Message\AMQPMessage $msg, string $action): void
    {
        $decoded = [];
        try {
            $decoded = json_decode($msg->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $sagaId  = $decoded['saga_id'] ?? '';
            $orderId = $decoded['order_id'] ?? '';
            $payload = $decoded['payload'] ?? [];

            if ($action === 'RESERVE') {
                $this->processReserve($sagaId, $orderId, $payload);
            } else {
                $this->processRelease($sagaId, $orderId, $payload);
            }

            $msg->ack();
        } catch (\Throwable $e) {
            Log::error('[InventoryConsumer] Failed to process message', [
                'error'   => $e->getMessage(),
                'saga_id' => $decoded['saga_id'] ?? 'unknown',
            ]);
            $msg->nack(false);
        }
    }

    private function processReserve(string $sagaId, string $orderId, array $payload): void
    {
        $items = $payload['items'] ?? [];

        Log::info('[InventoryConsumer] Processing reserve request', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
            'items'    => count($items),
        ]);

        $success = $this->inventoryService->reserveStock($sagaId, $orderId, $items);

        $replyType = $success ? 'INVENTORY_RESERVED' : 'INVENTORY_RESERVATION_FAILED';

        $reply = [
            'saga_id'   => $sagaId,
            'order_id'  => $orderId,
            'type'      => $replyType,
            'success'   => $success,
            'data'      => $success ? ['order_id' => $orderId, 'items_count' => count($items)] : [],
            'error'     => $success ? '' : 'Insufficient stock for one or more items',
            'timestamp' => now()->toIso8601String(),
        ];

        $this->publisher->publish(
            config('rabbitmq.exchanges.replies'),
            config('rabbitmq.queues.saga_replies'),
            $reply
        );

        Log::info('[InventoryConsumer] Reply sent', ['type' => $replyType, 'saga_id' => $sagaId]);
    }

    private function processRelease(string $sagaId, string $orderId, array $payload): void
    {
        Log::info('[InventoryConsumer] Processing release request', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);

        $success = $this->inventoryService->releaseReservation($sagaId, $orderId);

        $reply = [
            'saga_id'   => $sagaId,
            'order_id'  => $orderId,
            'type'      => 'INVENTORY_RELEASED',
            'success'   => $success,
            'data'      => [],
            'error'     => $success ? '' : 'Failed to release inventory',
            'timestamp' => now()->toIso8601String(),
        ];

        $this->publisher->publish(
            config('rabbitmq.exchanges.replies'),
            config('rabbitmq.queues.saga_replies'),
            $reply
        );

        Log::info('[InventoryConsumer] Release reply sent', ['saga_id' => $sagaId]);
    }
}

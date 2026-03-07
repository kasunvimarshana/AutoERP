<?php

namespace App\Saga\Steps;

use App\Messaging\RabbitMQPublisher;
use Illuminate\Support\Facades\Log;

class ReserveInventoryStep
{
    public function __construct(
        private readonly RabbitMQPublisher $publisher
    ) {}

    public function execute(string $sagaId, string $orderId, array $items): void
    {
        $message = [
            'saga_id'   => $sagaId,
            'order_id'  => $orderId,
            'type'      => 'RESERVE_INVENTORY',
            'payload'   => [
                'items' => $items,
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $this->publisher->publish(
            config('rabbitmq.exchanges.commands'),
            config('rabbitmq.queues.reserve_inventory'),
            $message
        );

        Log::info('[Step] ReserveInventory published', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);
    }
}

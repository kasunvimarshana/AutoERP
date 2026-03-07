<?php

namespace App\Saga\Steps;

use App\Messaging\RabbitMQPublisher;
use Illuminate\Support\Facades\Log;

class ReleaseInventoryStep
{
    public function __construct(
        private readonly RabbitMQPublisher $publisher
    ) {}

    public function execute(string $sagaId, string $orderId, array $reservationData = []): void
    {
        $message = [
            'saga_id'   => $sagaId,
            'order_id'  => $orderId,
            'type'      => 'RELEASE_INVENTORY',
            'payload'   => [
                'order_id'         => $orderId,
                'saga_id'          => $sagaId,
                'reservation_data' => $reservationData,
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $this->publisher->publish(
            config('rabbitmq.exchanges.commands'),
            config('rabbitmq.queues.release_inventory'),
            $message
        );

        Log::info('[Step] ReleaseInventory (compensation) published', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);
    }
}

<?php

namespace App\Saga\Steps;

use App\Messaging\RabbitMQPublisher;
use Illuminate\Support\Facades\Log;

class SendNotificationStep
{
    public function __construct(
        private readonly RabbitMQPublisher $publisher
    ) {}

    public function execute(
        string $sagaId,
        string $orderId,
        string $customerEmail,
        array  $items,
        float  $totalAmount
    ): void {
        $message = [
            'saga_id'   => $sagaId,
            'order_id'  => $orderId,
            'type'      => 'SEND_NOTIFICATION',
            'payload'   => [
                'customer_email' => $customerEmail,
                'order_id'       => $orderId,
                'items'          => $items,
                'total_amount'   => $totalAmount,
                'saga_id'        => $sagaId,
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $this->publisher->publish(
            config('rabbitmq.exchanges.commands'),
            config('rabbitmq.queues.send_notification'),
            $message
        );

        Log::info('[Step] SendNotification published', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);
    }
}

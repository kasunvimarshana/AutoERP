<?php

namespace App\Saga\Steps;

use App\Messaging\RabbitMQPublisher;
use Illuminate\Support\Facades\Log;

class ProcessPaymentStep
{
    public function __construct(
        private readonly RabbitMQPublisher $publisher
    ) {}

    public function execute(
        string $sagaId,
        string $orderId,
        string $customerId,
        float  $amount
    ): void {
        $message = [
            'saga_id'   => $sagaId,
            'order_id'  => $orderId,
            'type'      => 'PROCESS_PAYMENT',
            'payload'   => [
                'amount'      => $amount,
                'order_id'    => $orderId,
                'customer_id' => $customerId,
                'saga_id'     => $sagaId,
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $this->publisher->publish(
            config('rabbitmq.exchanges.commands'),
            config('rabbitmq.queues.process_payment'),
            $message
        );

        Log::info('[Step] ProcessPayment published', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
            'amount'   => $amount,
        ]);
    }
}

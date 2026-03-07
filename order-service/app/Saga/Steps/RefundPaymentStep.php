<?php

namespace App\Saga\Steps;

use App\Messaging\RabbitMQPublisher;
use Illuminate\Support\Facades\Log;

class RefundPaymentStep
{
    public function __construct(
        private readonly RabbitMQPublisher $publisher
    ) {}

    public function execute(string $sagaId, string $orderId, array $paymentData = []): void
    {
        $message = [
            'saga_id'   => $sagaId,
            'order_id'  => $orderId,
            'type'      => 'REFUND_PAYMENT',
            'payload'   => [
                'order_id'       => $orderId,
                'saga_id'        => $sagaId,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'amount'         => $paymentData['amount'] ?? null,
                'payment_id'     => $paymentData['payment_id'] ?? null,
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $this->publisher->publish(
            config('rabbitmq.exchanges.commands'),
            config('rabbitmq.queues.refund_payment'),
            $message
        );

        Log::info('[Step] RefundPayment (compensation) published', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);
    }
}

<?php
namespace App\Saga;

use App\Services\NotificationService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class NotificationSagaHandler
{
    public function __construct(
        private NotificationService $service,
        private AMQPChannel $channel,
        private string $exchange = 'saga.events',
    ) {}

    public function handleSendOrderConfirmation(array $payload): void
    {
        $sagaId    = $payload['saga_id'];
        $orderId   = $payload['order_id'];
        $tenantId  = $payload['tenant_id'];
        $recipient = $payload['recipient'] ?? [];
        $orderData = $payload['order_data'] ?? [];

        try {
            $this->service->sendOrderConfirmation($sagaId, $orderId, $tenantId, $recipient, $orderData);
            $this->publishEvent('notification.sent', [
                'saga_id'  => $sagaId,
                'order_id' => $orderId,
                'type'     => 'order_confirmed',
            ]);
        } catch (\Throwable $e) {
            Log::error('Notification failed', ['saga_id' => $sagaId, 'error' => $e->getMessage()]);
            $this->publishEvent('notification.failed', [
                'saga_id'  => $sagaId,
                'order_id' => $orderId,
                'type'     => 'order_confirmed',
                'reason'   => $e->getMessage(),
            ]);
        }
    }

    public function handleSendOrderCancellation(array $payload): void
    {
        $sagaId    = $payload['saga_id'];
        $orderId   = $payload['order_id'];
        $tenantId  = $payload['tenant_id'];
        $recipient = $payload['recipient'] ?? [];
        $orderData = $payload['order_data'] ?? [];

        try {
            $this->service->sendOrderCancellation($sagaId, $orderId, $tenantId, $recipient, $orderData);
            $this->publishEvent('notification.sent', [
                'saga_id'  => $sagaId,
                'order_id' => $orderId,
                'type'     => 'order_cancelled',
            ]);
        } catch (\Throwable $e) {
            Log::error('Cancellation notification failed', ['saga_id' => $sagaId, 'error' => $e->getMessage()]);
            $this->publishEvent('notification.failed', [
                'saga_id'  => $sagaId,
                'order_id' => $orderId,
                'type'     => 'order_cancelled',
                'reason'   => $e->getMessage(),
            ]);
        }
    }

    private function publishEvent(string $routingKey, array $data): void
    {
        $msg = new AMQPMessage(
            json_encode(array_merge($data, ['timestamp' => now()->toIso8601String()])),
            ['content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );
        $this->channel->basic_publish($msg, $this->exchange, $routingKey);
        Log::info("Event published: {$routingKey}", $data);
    }
}

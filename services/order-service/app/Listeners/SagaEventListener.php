<?php

namespace App\Listeners;

use App\Saga\OrderSagaOrchestrator;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * SagaEventListener
 *
 * Maps incoming RabbitMQ reply events to the OrderSagaOrchestrator's handlers.
 *
 * Events consumed from "saga.orchestrator.replies":
 *   inventory.reserved           → handleStepSuccess  (RESERVE_INVENTORY)
 *   inventory.reservation_failed → handleStepFailure  (RESERVE_INVENTORY)
 *   inventory.released           → compensation ack   (RESERVE_INVENTORY)
 *   payment.processed            → handleStepSuccess  (PROCESS_PAYMENT)
 *   payment.failed               → handleStepFailure  (PROCESS_PAYMENT)
 *   payment.refunded             → compensation ack   (PROCESS_PAYMENT)
 *   notification.sent            → handleStepSuccess  (SEND_NOTIFICATION) → completes saga
 *   notification.failed          → handleStepFailure  (SEND_NOTIFICATION)
 */
class SagaEventListener
{
    public function __construct(private readonly OrderSagaOrchestrator $orchestrator) {}

    /**
     * Route an incoming event payload to the correct orchestrator method.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, AMQPMessage $message): bool
    {
        $event   = $payload['event']    ?? '';
        $sagaId  = $payload['saga_id']  ?? '';
        $orderId = $payload['order_id'] ?? null;

        if (empty($event) || empty($sagaId)) {
            Log::warning('[SagaEventListener] Received message missing event or saga_id.', [
                'payload' => $payload,
            ]);
            // Ack to avoid infinite requeue of poison messages.
            return true;
        }

        Log::info('[SagaEventListener] Processing event.', [
            'event'    => $event,
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);

        try {
            match ($event) {
                // -----------------------------------------------------------------
                // Inventory
                // -----------------------------------------------------------------
                'inventory.reserved' =>
                    $this->orchestrator->handleStepSuccess(
                        $sagaId,
                        'RESERVE_INVENTORY',
                        $payload
                    ),

                'inventory.reservation_failed' =>
                    $this->orchestrator->handleStepFailure(
                        $sagaId,
                        'RESERVE_INVENTORY',
                        $payload['error'] ?? 'Inventory reservation failed',
                        $payload
                    ),

                // Compensation ack – no further saga action needed.
                'inventory.released' => $this->logCompensationAck($event, $sagaId),

                // -----------------------------------------------------------------
                // Payment
                // -----------------------------------------------------------------
                'payment.processed' =>
                    $this->orchestrator->handleStepSuccess(
                        $sagaId,
                        'PROCESS_PAYMENT',
                        $payload
                    ),

                'payment.failed' =>
                    $this->orchestrator->handleStepFailure(
                        $sagaId,
                        'PROCESS_PAYMENT',
                        $payload['error'] ?? 'Payment processing failed',
                        $payload
                    ),

                // Compensation ack.
                'payment.refunded' => $this->logCompensationAck($event, $sagaId),

                // -----------------------------------------------------------------
                // Notification
                // -----------------------------------------------------------------
                'notification.sent' =>
                    $this->orchestrator->handleStepSuccess(
                        $sagaId,
                        'SEND_NOTIFICATION',
                        $payload
                    ),

                'notification.failed' =>
                    $this->orchestrator->handleStepFailure(
                        $sagaId,
                        'SEND_NOTIFICATION',
                        $payload['error'] ?? 'Notification failed',
                        $payload
                    ),

                'notification.cancellation_sent' => $this->logCompensationAck($event, $sagaId),

                default => Log::warning('[SagaEventListener] Unhandled event.', [
                    'event'   => $event,
                    'saga_id' => $sagaId,
                ]),
            };
        } catch (\Throwable $e) {
            Log::error('[SagaEventListener] Exception while handling event.', [
                'event'   => $event,
                'saga_id' => $sagaId,
                'error'   => $e->getMessage(),
            ]);
            // Nack so the message is requeued for retry.
            return false;
        }

        return true;
    }

    private function logCompensationAck(string $event, string $sagaId): void
    {
        Log::info('[SagaEventListener] Compensation acknowledgement received.', [
            'event'   => $event,
            'saga_id' => $sagaId,
        ]);
    }
}

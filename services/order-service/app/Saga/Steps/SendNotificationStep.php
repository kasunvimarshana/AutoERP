<?php

namespace App\Saga\Steps;

use App\Saga\SagaStep;
use App\Saga\SagaStepResult;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Step 4 – SEND_NOTIFICATION
 *
 * Forward  : Publish a SEND_ORDER_CONFIRMATION command to the notification service.
 *            This is the final saga step; completion marks the saga as successful.
 *
 * Compensate: Publish a SEND_ORDER_CANCELLATION command so the customer is
 *             informed that their order was cancelled.
 */
class SendNotificationStep extends SagaStep
{
    public function __construct(private readonly RabbitMQService $rabbitMQ) {}

    public function name(): string
    {
        return 'SEND_NOTIFICATION';
    }

    /**
     * @param  array{
     *     saga_id:      string,
     *     order_id:     int,
     *     tenant_id:    int,
     *     customer_id:  int|string,
     *     total_amount: float|string,
     *     currency:     string,
     *     items:        array,
     * } $payload
     */
    public function execute(array $payload): SagaStepResult
    {
        try {
            $this->rabbitMQ->publishCommand('notification', 'SEND_ORDER_CONFIRMATION', [
                'saga_id'      => $payload['saga_id'],
                'order_id'     => $payload['order_id'],
                'tenant_id'    => $payload['tenant_id'],
                'customer_id'  => $payload['customer_id'],
                'total_amount' => $payload['total_amount'],
                'currency'     => $payload['currency'] ?? 'USD',
                'items'        => $payload['items'] ?? [],
                'type'         => 'order_confirmation',
                'reply_to'     => 'saga.orchestrator.replies',
                'timestamp'    => now()->toIso8601String(),
            ]);

            Log::info('[Saga:SendNotificationStep] SEND_ORDER_CONFIRMATION command published.', [
                'saga_id'  => $payload['saga_id'],
                'order_id' => $payload['order_id'],
            ]);

            return SagaStepResult::success(['async' => true, 'awaiting_event' => 'notification.sent']);
        } catch (Throwable $e) {
            Log::error('[Saga:SendNotificationStep] Failed to publish command.', [
                'saga_id' => $payload['saga_id'] ?? null,
                'error'   => $e->getMessage(),
            ]);

            return SagaStepResult::failure($e->getMessage());
        }
    }

    /**
     * Publish a cancellation notification to inform the customer.
     *
     * @param  array{saga_id: string, order_id: int, reason?: string}  $payload
     */
    public function compensate(array $payload): SagaStepResult
    {
        try {
            $this->rabbitMQ->publishCommand('notification', 'SEND_ORDER_CANCELLATION', [
                'saga_id'   => $payload['saga_id'],
                'order_id'  => $payload['order_id'],
                'reason'    => $payload['reason'] ?? 'saga_compensation',
                'type'      => 'order_cancellation',
                'reply_to'  => 'saga.orchestrator.replies',
                'timestamp' => now()->toIso8601String(),
            ]);

            Log::info('[Saga:SendNotificationStep] SEND_ORDER_CANCELLATION compensation published.', [
                'saga_id'  => $payload['saga_id'],
                'order_id' => $payload['order_id'],
            ]);

            return SagaStepResult::success(['async' => true, 'awaiting_event' => 'notification.cancellation_sent']);
        } catch (Throwable $e) {
            Log::error('[Saga:SendNotificationStep] Compensation publish failed.', [
                'payload' => $payload,
                'error'   => $e->getMessage(),
            ]);

            return SagaStepResult::failure($e->getMessage());
        }
    }
}

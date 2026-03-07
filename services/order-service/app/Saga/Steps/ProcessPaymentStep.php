<?php

namespace App\Saga\Steps;

use App\Saga\SagaStep;
use App\Saga\SagaStepResult;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Step 3 – PROCESS_PAYMENT
 *
 * Forward  : Publish a PROCESS_PAYMENT command to the payment service.
 *            The saga advances when the payment.processed event arrives.
 *
 * Compensate: Publish a REFUND_PAYMENT command to reverse the charge.
 */
class ProcessPaymentStep extends SagaStep
{
    public function __construct(private readonly RabbitMQService $rabbitMQ) {}

    public function name(): string
    {
        return 'PROCESS_PAYMENT';
    }

    /**
     * @param  array{
     *     saga_id:        string,
     *     order_id:       int,
     *     tenant_id:      int,
     *     customer_id:    int|string,
     *     total_amount:   float|string,
     *     currency:       string,
     *     payment_method?: array,
     * } $payload
     */
    public function execute(array $payload): SagaStepResult
    {
        try {
            $this->rabbitMQ->publishCommand('payment', 'PROCESS_PAYMENT', [
                'saga_id'        => $payload['saga_id'],
                'order_id'       => $payload['order_id'],
                'tenant_id'      => $payload['tenant_id'],
                'customer_id'    => $payload['customer_id'],
                'amount'         => $payload['total_amount'],
                'currency'       => $payload['currency'] ?? 'USD',
                'payment_method' => $payload['payment_method'] ?? null,
                'reply_to'       => 'saga.orchestrator.replies',
                'timestamp'      => now()->toIso8601String(),
            ]);

            Log::info('[Saga:ProcessPaymentStep] PROCESS_PAYMENT command published.', [
                'saga_id'  => $payload['saga_id'],
                'order_id' => $payload['order_id'],
            ]);

            return SagaStepResult::success(['async' => true, 'awaiting_event' => 'payment.processed']);
        } catch (Throwable $e) {
            Log::error('[Saga:ProcessPaymentStep] Failed to publish command.', [
                'saga_id' => $payload['saga_id'] ?? null,
                'error'   => $e->getMessage(),
            ]);

            return SagaStepResult::failure($e->getMessage());
        }
    }

    /**
     * @param  array{saga_id: string, order_id: int, payment_id?: string, amount?: float}  $payload
     */
    public function compensate(array $payload): SagaStepResult
    {
        try {
            $this->rabbitMQ->publishCommand('payment', 'REFUND_PAYMENT', [
                'saga_id'    => $payload['saga_id'],
                'order_id'   => $payload['order_id'],
                'payment_id' => $payload['payment_id'] ?? null,
                'amount'     => $payload['amount'] ?? null,
                'reason'     => 'saga_compensation',
                'reply_to'   => 'saga.orchestrator.replies',
                'timestamp'  => now()->toIso8601String(),
            ]);

            Log::info('[Saga:ProcessPaymentStep] REFUND_PAYMENT compensation command published.', [
                'saga_id'  => $payload['saga_id'],
                'order_id' => $payload['order_id'],
            ]);

            return SagaStepResult::success(['async' => true, 'awaiting_event' => 'payment.refunded']);
        } catch (Throwable $e) {
            Log::error('[Saga:ProcessPaymentStep] Compensation publish failed.', [
                'payload' => $payload,
                'error'   => $e->getMessage(),
            ]);

            return SagaStepResult::failure($e->getMessage());
        }
    }
}

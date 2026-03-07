<?php

namespace App\Saga\Steps;

use App\Saga\SagaStep;
use App\Saga\SagaStepResult;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Step 2 – RESERVE_INVENTORY
 *
 * Forward  : Publish a RESERVE_INVENTORY command to the inventory service via RabbitMQ.
 *            The step is considered "dispatched" – the saga advances only when the
 *            inventory.reserved event arrives back on saga.orchestrator.replies.
 *
 * Compensate: Publish a RELEASE_INVENTORY command so the inventory service
 *             releases previously reserved stock.
 */
class ReserveInventoryStep extends SagaStep
{
    public function __construct(private readonly RabbitMQService $rabbitMQ) {}

    public function name(): string
    {
        return 'RESERVE_INVENTORY';
    }

    /**
     * @param  array{
     *     saga_id:     string,
     *     order_id:    int,
     *     tenant_id:   int,
     *     customer_id: int|string,
     *     items:       array,
     * } $payload
     */
    public function execute(array $payload): SagaStepResult
    {
        try {
            $this->rabbitMQ->publishCommand('inventory', 'RESERVE_INVENTORY', [
                'saga_id'     => $payload['saga_id'],
                'order_id'    => $payload['order_id'],
                'tenant_id'   => $payload['tenant_id'],
                'customer_id' => $payload['customer_id'],
                'items'       => $payload['items'],
                'reply_to'    => 'saga.orchestrator.replies',
                'timestamp'   => now()->toIso8601String(),
            ]);

            Log::info('[Saga:ReserveInventoryStep] RESERVE_INVENTORY command published.', [
                'saga_id'  => $payload['saga_id'],
                'order_id' => $payload['order_id'],
            ]);

            // Async step – result arrives via saga.orchestrator.replies queue.
            return SagaStepResult::success(['async' => true, 'awaiting_event' => 'inventory.reserved']);
        } catch (Throwable $e) {
            Log::error('[Saga:ReserveInventoryStep] Failed to publish command.', [
                'saga_id' => $payload['saga_id'] ?? null,
                'error'   => $e->getMessage(),
            ]);

            return SagaStepResult::failure($e->getMessage());
        }
    }

    /**
     * @param  array{saga_id: string, order_id: int, reservation_id?: string}  $payload
     */
    public function compensate(array $payload): SagaStepResult
    {
        try {
            $this->rabbitMQ->publishCommand('inventory', 'RELEASE_INVENTORY', [
                'saga_id'        => $payload['saga_id'],
                'order_id'       => $payload['order_id'],
                'reservation_id' => $payload['reservation_id'] ?? null,
                'reply_to'       => 'saga.orchestrator.replies',
                'timestamp'      => now()->toIso8601String(),
            ]);

            Log::info('[Saga:ReserveInventoryStep] RELEASE_INVENTORY compensation command published.', [
                'saga_id'  => $payload['saga_id'],
                'order_id' => $payload['order_id'],
            ]);

            return SagaStepResult::success(['async' => true, 'awaiting_event' => 'inventory.released']);
        } catch (Throwable $e) {
            Log::error('[Saga:ReserveInventoryStep] Compensation publish failed.', [
                'payload' => $payload,
                'error'   => $e->getMessage(),
            ]);

            return SagaStepResult::failure($e->getMessage());
        }
    }
}

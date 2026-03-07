<?php

namespace App\Saga\Steps;

use App\Models\Order;
use App\Saga\SagaStep;
use App\Saga\SagaStepResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Step 1 – CREATE_ORDER
 *
 * Forward : Persist the order record in the local MySQL database.
 * Compensate: Mark the order as cancelled (local rollback).
 */
class CreateOrderStep extends SagaStep
{
    public function name(): string
    {
        return 'CREATE_ORDER';
    }

    /**
     * Persist a new Order row and return its ID.
     *
     * @param  array{
     *     tenant_id:    int,
     *     customer_id:  int|string,
     *     items:        array,
     *     total_amount: float|string,
     *     currency:     string,
     *     metadata?:    array,
     *     saga_id:      string,
     * } $payload
     */
    public function execute(array $payload): SagaStepResult
    {
        try {
            /** @var Order $order */
            $order = DB::transaction(function () use ($payload): Order {
                return Order::create([
                    'tenant_id'    => $payload['tenant_id'],
                    'customer_id'  => $payload['customer_id'],
                    'status'       => Order::STATUS_PENDING,
                    'total_amount' => $payload['total_amount'],
                    'currency'     => $payload['currency'] ?? 'USD',
                    'items'        => $payload['items'],
                    'metadata'     => $payload['metadata'] ?? [],
                    'saga_id'      => $payload['saga_id'],
                ]);
            });

            Log::info('[Saga:CreateOrderStep] Order created.', [
                'order_id' => $order->id,
                'saga_id'  => $payload['saga_id'],
            ]);

            return SagaStepResult::success(['order_id' => $order->id]);
        } catch (Throwable $e) {
            Log::error('[Saga:CreateOrderStep] Failed to create order.', [
                'saga_id' => $payload['saga_id'] ?? null,
                'error'   => $e->getMessage(),
            ]);

            return SagaStepResult::failure($e->getMessage());
        }
    }

    /**
     * Cancel the order locally – no remote calls needed.
     *
     * @param  array{order_id: int}  $payload
     */
    public function compensate(array $payload): SagaStepResult
    {
        try {
            $order = Order::find($payload['order_id'] ?? null);

            if ($order === null) {
                Log::warning('[Saga:CreateOrderStep] Compensate – order not found.', $payload);
                return SagaStepResult::success(['skipped' => true]);
            }

            $order->cancel();

            Log::info('[Saga:CreateOrderStep] Order cancelled (compensation).', [
                'order_id' => $order->id,
                'saga_id'  => $payload['saga_id'] ?? null,
            ]);

            return SagaStepResult::success(['order_id' => $order->id, 'status' => Order::STATUS_CANCELLED]);
        } catch (Throwable $e) {
            Log::error('[Saga:CreateOrderStep] Compensation failed.', [
                'payload' => $payload,
                'error'   => $e->getMessage(),
            ]);

            return SagaStepResult::failure($e->getMessage());
        }
    }
}

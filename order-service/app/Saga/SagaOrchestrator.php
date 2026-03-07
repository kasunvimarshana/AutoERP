<?php

namespace App\Saga;

use App\Models\Order;
use App\Models\SagaState;
use App\Saga\Steps\ProcessPaymentStep;
use App\Saga\Steps\RefundPaymentStep;
use App\Saga\Steps\ReleaseInventoryStep;
use App\Saga\Steps\ReserveInventoryStep;
use App\Saga\Steps\SendNotificationStep;
use Illuminate\Support\Facades\Log;
use Predis\Client as RedisClient;
use Ramsey\Uuid\Uuid;

class SagaOrchestrator
{
    private string $redisPrefix;

    public function __construct(
        private readonly ReserveInventoryStep  $reserveInventoryStep,
        private readonly ProcessPaymentStep    $processPaymentStep,
        private readonly SendNotificationStep  $sendNotificationStep,
        private readonly ReleaseInventoryStep  $releaseInventoryStep,
        private readonly RefundPaymentStep     $refundPaymentStep,
        private readonly RedisClient           $redis
    ) {
        $this->redisPrefix = config('saga.redis_prefix', 'saga:');
    }

    /**
     * Start a new saga for the given order.
     */
    public function startSaga(Order $order): string
    {
        $sagaId = Uuid::uuid4()->toString();

        $sagaData = [
            'saga_id'             => $sagaId,
            'order_id'            => $order->id,
            'customer_id'         => $order->customer_id,
            'customer_email'      => $order->customer_email,
            'items'               => $order->items,
            'total_amount'        => (string) $order->total_amount,
            'status'              => SagaState::STATUS_STARTED,
            'current_step'        => 'RESERVE_INVENTORY',
            'compensation_data'   => [],
            'started_at'          => now()->toIso8601String(),
        ];

        // Persist saga state in database
        SagaState::create([
            'saga_id'           => $sagaId,
            'order_id'          => $order->id,
            'current_step'      => 'RESERVE_INVENTORY',
            'status'            => SagaState::STATUS_STARTED,
            'compensation_data' => [],
        ]);

        // Persist saga state in Redis for fast access
        $this->redis->setex(
            $this->redisPrefix . $sagaId,
            config('saga.timeout', 300),
            json_encode($sagaData)
        );

        // Publish the first command
        $this->reserveInventoryStep->execute($sagaId, $order->id, $order->items);

        Log::info('[Saga] Started', ['saga_id' => $sagaId, 'order_id' => $order->id]);

        return $sagaId;
    }

    /**
     * Handle inventory reservation response.
     */
    public function handleInventoryResponse(array $event): void
    {
        $sagaId  = $event['saga_id'];
        $orderId = $event['order_id'];
        $success = (bool) ($event['success'] ?? false);

        Log::info('[Saga] Inventory response received', [
            'saga_id' => $sagaId,
            'success' => $success,
            'event'   => $event,
        ]);

        if ($success) {
            $reservationData = $event['data'] ?? [];

            // Store compensation data so we can release inventory if needed later
            $this->updateSagaState(
                $sagaId,
                'PROCESS_PAYMENT',
                SagaState::STATUS_INVENTORY_RESERVED,
                ['inventory_reservation' => $reservationData]
            );

            $order = Order::find($orderId);
            if ($order) {
                $order->update(['saga_state' => SagaState::STATUS_INVENTORY_RESERVED]);
                $this->processPaymentStep->execute(
                    $sagaId,
                    $orderId,
                    $order->customer_id,
                    (float) $order->total_amount
                );
            }
        } else {
            $errorMessage = $event['error'] ?? 'Inventory reservation failed';
            $this->updateSagaState($sagaId, 'RESERVE_INVENTORY', SagaState::STATUS_FAILED, []);
            $this->failOrder($orderId, $errorMessage);

            Log::warning('[Saga] Inventory reservation failed, saga failed', [
                'saga_id' => $sagaId,
                'error'   => $errorMessage,
            ]);
        }
    }

    /**
     * Handle payment processing response.
     */
    public function handlePaymentResponse(array $event): void
    {
        $sagaId  = $event['saga_id'];
        $orderId = $event['order_id'];
        $success = (bool) ($event['success'] ?? false);

        Log::info('[Saga] Payment response received', [
            'saga_id' => $sagaId,
            'success' => $success,
        ]);

        if ($success) {
            $paymentData = $event['data'] ?? [];

            $existingState  = $this->getSagaFromRedis($sagaId);
            $compensationData = array_merge(
                $existingState['compensation_data'] ?? [],
                ['payment' => $paymentData]
            );

            $this->updateSagaState(
                $sagaId,
                'SEND_NOTIFICATION',
                SagaState::STATUS_PAYMENT_PROCESSED,
                $compensationData
            );

            $order = Order::find($orderId);
            if ($order) {
                $order->update(['saga_state' => SagaState::STATUS_PAYMENT_PROCESSED]);
                $this->sendNotificationStep->execute(
                    $sagaId,
                    $orderId,
                    $order->customer_email,
                    $order->items,
                    (float) $order->total_amount
                );
            }
        } else {
            $errorMessage = $event['error'] ?? 'Payment processing failed';

            $this->updateSagaState($sagaId, 'PROCESS_PAYMENT', SagaState::STATUS_COMPENSATION_STARTED, []);
            $this->compensate($sagaId, 'INVENTORY_RESERVED');
            $this->failOrder($orderId, $errorMessage);

            Log::warning('[Saga] Payment failed, starting compensation', [
                'saga_id' => $sagaId,
                'error'   => $errorMessage,
            ]);
        }
    }

    /**
     * Handle notification sent response.
     */
    public function handleNotificationResponse(array $event): void
    {
        $sagaId  = $event['saga_id'];
        $orderId = $event['order_id'];
        $success = (bool) ($event['success'] ?? false);

        Log::info('[Saga] Notification response received', [
            'saga_id' => $sagaId,
            'success' => $success,
        ]);

        if ($success) {
            $this->updateSagaState($sagaId, 'COMPLETED', SagaState::STATUS_COMPLETED, []);

            $order = Order::find($orderId);
            if ($order) {
                $order->update([
                    'status'     => Order::STATUS_CONFIRMED,
                    'saga_state' => SagaState::STATUS_COMPLETED,
                ]);
            }

            // Clean up Redis entry after completion
            $this->redis->del($this->redisPrefix . $sagaId);

            Log::info('[Saga] Completed successfully', [
                'saga_id'  => $sagaId,
                'order_id' => $orderId,
            ]);
        } else {
            // Notification failed — this is non-critical; order is still confirmed
            // but we log it and do not rollback payment/inventory for a notification failure
            $errorMessage = $event['error'] ?? 'Notification sending failed';

            $this->updateSagaState($sagaId, 'COMPLETED', SagaState::STATUS_COMPLETED, []);

            $order = Order::find($orderId);
            if ($order) {
                $order->update([
                    'status'     => Order::STATUS_CONFIRMED,
                    'saga_state' => SagaState::STATUS_COMPLETED,
                ]);
            }

            Log::warning('[Saga] Notification failed (order still confirmed)', [
                'saga_id' => $sagaId,
                'error'   => $errorMessage,
            ]);
        }
    }

    /**
     * Trigger compensating transactions to roll back completed steps.
     */
    public function compensate(string $sagaId, string $fromStep = null): void
    {
        Log::info('[Saga] Starting compensation', ['saga_id' => $sagaId, 'from_step' => $fromStep]);

        $sagaData = $this->getSagaFromRedis($sagaId);

        if (empty($sagaData)) {
            // Fall back to DB
            $sagaState = SagaState::where('saga_id', $sagaId)->first();
            if ($sagaState) {
                $sagaData = [
                    'saga_id'           => $sagaState->saga_id,
                    'order_id'          => $sagaState->order_id,
                    'compensation_data' => $sagaState->compensation_data ?? [],
                    'status'            => $sagaState->status,
                ];
            }
        }

        if (empty($sagaData)) {
            Log::error('[Saga] Cannot compensate — saga data not found', ['saga_id' => $sagaId]);
            return;
        }

        $compensationData = $sagaData['compensation_data'] ?? [];
        $orderId          = $sagaData['order_id'];
        $resolvedStep     = $fromStep ?? ($sagaData['current_step'] ?? null);

        // Determine which compensating actions are needed based on how far the saga progressed
        $compensationSteps = $this->resolveCompensationSteps($resolvedStep, $sagaData['status'] ?? '');

        foreach ($compensationSteps as $step) {
            try {
                match ($step) {
                    'RELEASE_INVENTORY' => $this->releaseInventoryStep->execute(
                        $sagaId,
                        $orderId,
                        $compensationData['inventory_reservation'] ?? []
                    ),
                    'REFUND_PAYMENT' => $this->refundPaymentStep->execute(
                        $sagaId,
                        $orderId,
                        $compensationData['payment'] ?? []
                    ),
                    default => Log::warning('[Saga] Unknown compensation step', ['step' => $step]),
                };

                Log::info("[Saga] Compensation step executed: {$step}", ['saga_id' => $sagaId]);
            } catch (\Throwable $e) {
                Log::error("[Saga] Compensation step failed: {$step}", [
                    'saga_id' => $sagaId,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $this->updateSagaState($sagaId, 'COMPENSATION', SagaState::STATUS_COMPENSATION_COMPLETED, $compensationData);
    }

    /**
     * Update saga state in both Redis and the database.
     */
    public function updateSagaState(
        string $sagaId,
        string $step,
        string $status,
        array $compensationData = []
    ): void {
        // Update database record
        SagaState::where('saga_id', $sagaId)->update([
            'current_step'      => $step,
            'status'            => $status,
            'compensation_data' => empty($compensationData) ? null : $compensationData,
        ]);

        // Update Redis entry
        $redisKey = $this->redisPrefix . $sagaId;
        $existing = $this->getSagaFromRedis($sagaId);

        if (! empty($existing)) {
            $existing['current_step']      = $step;
            $existing['status']            = $status;
            if (! empty($compensationData)) {
                $existing['compensation_data'] = array_merge(
                    $existing['compensation_data'] ?? [],
                    $compensationData
                );
            }
            $existing['updated_at'] = now()->toIso8601String();

            $this->redis->setex(
                $redisKey,
                config('saga.timeout', 300),
                json_encode($existing)
            );
        }

        Log::info('[Saga] State updated', [
            'saga_id' => $sagaId,
            'step'    => $step,
            'status'  => $status,
        ]);
    }

    /**
     * Get saga data from Redis.
     */
    private function getSagaFromRedis(string $sagaId): array
    {
        $data = $this->redis->get($this->redisPrefix . $sagaId);
        if ($data) {
            return json_decode($data, true) ?? [];
        }
        return [];
    }

    /**
     * Mark an order as failed with an error message.
     */
    private function failOrder(string $orderId, string $errorMessage): void
    {
        $order = Order::find($orderId);
        if ($order) {
            $order->update([
                'status'     => Order::STATUS_FAILED,
                'saga_state' => SagaState::STATUS_FAILED,
            ]);
        }

        SagaState::where('order_id', $orderId)
            ->whereNotIn('status', [SagaState::STATUS_FAILED, SagaState::STATUS_COMPLETED])
            ->update([
                'status'        => SagaState::STATUS_FAILED,
                'error_message' => $errorMessage,
            ]);
    }

    /**
     * Resolve which compensating steps need to run based on how far we progressed.
     */
    private function resolveCompensationSteps(string $fromStep, string $status): array
    {
        $steps = [];

        if (in_array($status, [
            SagaState::STATUS_PAYMENT_PROCESSED,
            SagaState::STATUS_COMPENSATION_STARTED,
        ], true)) {
            $steps[] = 'REFUND_PAYMENT';
        }

        if (in_array($status, [
            SagaState::STATUS_INVENTORY_RESERVED,
            SagaState::STATUS_PAYMENT_PROCESSED,
            SagaState::STATUS_COMPENSATION_STARTED,
        ], true) || $fromStep === 'INVENTORY_RESERVED') {
            $steps[] = 'RELEASE_INVENTORY';
        }

        return $steps;
    }
}

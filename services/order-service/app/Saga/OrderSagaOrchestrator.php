<?php

namespace App\Saga;

use App\Models\Order;
use App\Models\SagaTransaction;
use App\Saga\Steps\CreateOrderStep;
use App\Saga\Steps\ProcessPaymentStep;
use App\Saga\Steps\ReserveInventoryStep;
use App\Saga\Steps\SendNotificationStep;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

/**
 * OrderSagaOrchestrator
 *
 * Coordinates the Order Placement Saga across multiple microservices using
 * the Saga Orchestration pattern.
 *
 * Saga steps (forward):
 *   1. CREATE_ORDER        – local: persist order to MySQL
 *   2. RESERVE_INVENTORY   – async: publish command → await inventory.reserved
 *   3. PROCESS_PAYMENT     – async: publish command → await payment.processed
 *   4. SEND_NOTIFICATION   – async: publish command → await notification.sent
 *
 * Compensating transactions (triggered in reverse order on failure):
 *   SEND_NOTIFICATION     → COMPENSATE_SEND_NOTIFICATION   (cancellation notification)
 *   PROCESS_PAYMENT       → COMPENSATE_PROCESS_PAYMENT     (refund)
 *   RESERVE_INVENTORY     → COMPENSATE_RESERVE_INVENTORY   (release stock)
 *   CREATE_ORDER          → COMPENSATE_CREATE_ORDER        (cancel order locally)
 *
 * State is persisted in:
 *   - MySQL   (orders + saga_transactions tables) for durability
 *   - Redis   (saga:{id}) for fast real-time status reads, with a 24 h TTL
 */
class OrderSagaOrchestrator
{
    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    public const STEPS = [
        SagaTransaction::STEP_CREATE_ORDER,
        SagaTransaction::STEP_RESERVE_INVENTORY,
        SagaTransaction::STEP_PROCESS_PAYMENT,
        SagaTransaction::STEP_SEND_NOTIFICATION,
    ];

    /**
     * Compensation steps executed in reverse order.
     * CREATE_ORDER is always included but operates locally (cancel order).
     */
    public const COMPENSATION_STEPS = [
        'COMPENSATE_SEND_NOTIFICATION',
        'COMPENSATE_PROCESS_PAYMENT',
        'COMPENSATE_RESERVE_INVENTORY',
        'COMPENSATE_CREATE_ORDER',
    ];

    private const REDIS_TTL_SECONDS = 86_400; // 24 hours

    // -------------------------------------------------------------------------
    // Dependencies
    // -------------------------------------------------------------------------

    public function __construct(
        private readonly CreateOrderStep      $createOrderStep,
        private readonly ReserveInventoryStep $reserveInventoryStep,
        private readonly ProcessPaymentStep   $processPaymentStep,
        private readonly SendNotificationStep $sendNotificationStep,
        private readonly RabbitMQService      $rabbitMQ,
    ) {}

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Start a new Order Placement Saga.
     *
     * Generates a unique saga ID, initialises state in Redis, then executes the
     * first step (CREATE_ORDER) synchronously.  Subsequent async steps are
     * triggered via RabbitMQ replies handled by SagaEventConsumer.
     *
     * @param  array{
     *     tenant_id:       int,
     *     customer_id:     int|string,
     *     items:           array,
     *     total_amount:    float|string,
     *     currency?:       string,
     *     metadata?:       array,
     *     payment_method?: array,
     * } $orderData
     */
    public function start(array $orderData): Order
    {
        $sagaId = (string) Str::uuid();

        Log::info('[Saga] Starting Order Placement Saga.', ['saga_id' => $sagaId]);

        // Initialise saga state in Redis.
        $this->updateSagaState($sagaId, [
            'saga_id'       => $sagaId,
            'status'        => 'started',
            'current_step'  => self::STEPS[0],
            'completed_steps' => [],
            'order_data'    => $orderData,
            'started_at'    => now()->toIso8601String(),
        ]);

        // Step 1 is always local – execute synchronously.
        $payload          = array_merge($orderData, ['saga_id' => $sagaId]);
        $result           = $this->createOrderStep->execute($payload);

        if ($result->isFailure()) {
            $this->updateSagaState($sagaId, array_merge(
                $this->getSagaState($sagaId),
                ['status' => 'failed', 'error' => $result->error]
            ));

            throw new \RuntimeException("Saga failed at CREATE_ORDER: {$result->error}");
        }

        $orderId = $result->data['order_id'];
        $order   = Order::findOrFail($orderId);

        // Persist the step record.
        $this->recordStep($sagaId, $orderId, SagaTransaction::STEP_CREATE_ORDER, $result->data);

        // Update saga state with order_id and advance.
        $this->updateSagaState($sagaId, array_merge(
            $this->getSagaState($sagaId),
            [
                'status'           => 'in_progress',
                'order_id'         => $orderId,
                'current_step'     => self::STEPS[1],
                'completed_steps'  => [self::STEPS[0]],
                'step_results'     => [self::STEPS[0] => $result->data],
            ]
        ));

        // Immediately dispatch Step 2 (RESERVE_INVENTORY) asynchronously.
        $this->executeStep($order, SagaTransaction::STEP_RESERVE_INVENTORY, $orderData);

        return $order;
    }

    // -------------------------------------------------------------------------
    // Event handlers (called by SagaEventConsumer)
    // -------------------------------------------------------------------------

    /**
     * Advance the saga after a step succeeds.
     *
     * @param  array<string, mixed>  $eventData  Raw event payload from RabbitMQ
     */
    public function handleStepSuccess(string $sagaId, string $step, array $eventData): void
    {
        $state = $this->getSagaState($sagaId);

        if (empty($state)) {
            Log::warning('[Saga] handleStepSuccess called for unknown saga.', [
                'saga_id' => $sagaId,
                'step'    => $step,
            ]);
            return;
        }

        Log::info('[Saga] Step succeeded.', ['saga_id' => $sagaId, 'step' => $step]);

        // Update the step record in MySQL.
        SagaTransaction::where('saga_id', $sagaId)
            ->where('step', $step)
            ->first()
            ?->markCompleted($eventData);

        // Record step result and completed steps.
        $completedSteps                = $state['completed_steps'] ?? [];
        $completedSteps[]              = $step;
        $stepResults                   = $state['step_results'] ?? [];
        $stepResults[$step]            = $eventData;

        $nextStep = $this->nextStep($step);

        if ($nextStep === null) {
            // All steps complete – saga succeeded.
            $this->completeSaga($sagaId, $state['order_id']);
            return;
        }

        // Advance state.
        $this->updateSagaState($sagaId, array_merge($state, [
            'current_step'    => $nextStep,
            'completed_steps' => $completedSteps,
            'step_results'    => $stepResults,
        ]));

        $order = Order::find($state['order_id']);

        if ($order === null) {
            Log::error('[Saga] Order not found during step advance.', [
                'saga_id'  => $sagaId,
                'order_id' => $state['order_id'],
            ]);
            return;
        }

        $this->executeStep($order, $nextStep, $state['order_data'] ?? []);
    }

    /**
     * Trigger compensation after a step fails.
     *
     * @param  array<string, mixed>  $eventData
     */
    public function handleStepFailure(string $sagaId, string $step, string $error, array $eventData = []): void
    {
        $state = $this->getSagaState($sagaId);

        if (empty($state)) {
            Log::warning('[Saga] handleStepFailure called for unknown saga.', [
                'saga_id' => $sagaId,
                'step'    => $step,
            ]);
            return;
        }

        Log::error('[Saga] Step failed – starting compensation.', [
            'saga_id' => $sagaId,
            'step'    => $step,
            'error'   => $error,
        ]);

        // Mark the step record as failed in MySQL.
        SagaTransaction::where('saga_id', $sagaId)
            ->where('step', $step)
            ->first()
            ?->markFailed($error, $eventData);

        // Update saga state to compensating.
        $this->updateSagaState($sagaId, array_merge($state, [
            'status'        => 'compensating',
            'failed_step'   => $step,
            'failure_error' => $error,
        ]));

        $order = Order::find($state['order_id'] ?? null);

        if ($order === null) {
            Log::error('[Saga] Order not found during compensation.', ['saga_id' => $sagaId]);
            return;
        }

        $this->executeCompensation($order, $step, $state);
    }

    // =========================================================================
    // State management
    // =========================================================================

    /**
     * Retrieve saga state from Redis.
     *
     * @return array<string, mixed>
     */
    public function getSagaState(string $sagaId): array
    {
        try {
            $raw = Redis::get("saga:{$sagaId}");

            return $raw ? json_decode($raw, true) : [];
        } catch (Throwable $e) {
            Log::error('[Saga] Failed to read saga state from Redis.', [
                'saga_id' => $sagaId,
                'error'   => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Persist saga state in Redis with a 24 h TTL.
     *
     * @param  array<string, mixed>  $state
     */
    public function updateSagaState(string $sagaId, array $state): void
    {
        try {
            $state['updated_at'] = now()->toIso8601String();

            Redis::setex("saga:{$sagaId}", self::REDIS_TTL_SECONDS, json_encode($state));
        } catch (Throwable $e) {
            Log::error('[Saga] Failed to write saga state to Redis.', [
                'saga_id' => $sagaId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Dispatch a forward saga step.
     */
    private function executeStep(Order $order, string $step, array $orderData): void
    {
        $sagaId  = $order->saga_id;
        $payload = $this->buildStepPayload($order, $orderData);

        // Create pending saga transaction record.
        SagaTransaction::create([
            'order_id'   => $order->id,
            'saga_id'    => $sagaId,
            'step'       => $step,
            'status'     => SagaTransaction::STATUS_PENDING,
            'payload'    => $payload,
            'started_at' => now(),
        ]);

        $stepInstance = $this->resolveStep($step);
        $result       = $stepInstance->execute($payload);

        if ($result->isFailure()) {
            // Synchronous failure (e.g. RabbitMQ publish error) – compensate immediately.
            $this->handleStepFailure($sagaId, $step, $result->error);
        }
        // Async success: step records "dispatched"; saga advances on reply event.
    }

    /**
     * Execute compensating transactions starting from the step that failed,
     * working backwards through previously completed steps.
     *
     * @param  array<string, mixed>  $state
     */
    private function executeCompensation(Order $order, string $failedStep, array $state): void
    {
        $sagaId          = $order->saga_id;
        $completedSteps  = $state['completed_steps'] ?? [];
        $stepResults     = $state['step_results']    ?? [];
        $orderData       = $state['order_data']      ?? [];

        // Only compensate steps that actually completed (in reverse).
        $stepsToCompensate = array_reverse($completedSteps);

        Log::info('[Saga] Executing compensation.', [
            'saga_id'            => $sagaId,
            'failed_step'        => $failedStep,
            'steps_to_compensate' => $stepsToCompensate,
        ]);

        foreach ($stepsToCompensate as $step) {
            $this->compensateStep($order, $step, $stepResults[$step] ?? [], $orderData);
        }

        // Fail the order locally.
        $order->fail();

        $this->updateSagaState($sagaId, array_merge($state, [
            'status'       => 'compensated',
            'completed_at' => now()->toIso8601String(),
        ]));

        // Emit an order.failed event so other services can react if needed.
        $this->rabbitMQ->publishEvent('order.failed', [
            'saga_id'  => $sagaId,
            'order_id' => $order->id,
            'reason'   => $state['failure_error'] ?? 'unknown',
        ]);

        Log::info('[Saga] Compensation complete. Order failed.', [
            'saga_id'  => $sagaId,
            'order_id' => $order->id,
        ]);
    }

    /**
     * Execute a single compensation step and record its result.
     *
     * @param  array<string, mixed>  $stepResult   The result stored when the forward step ran
     * @param  array<string, mixed>  $orderData    Original order payload
     */
    private function compensateStep(Order $order, string $step, array $stepResult, array $orderData): void
    {
        $sagaId       = $order->saga_id;
        $stepInstance = $this->resolveStep($step);
        $payload      = array_merge(
            $this->buildStepPayload($order, $orderData),
            $stepResult,
            ['saga_id' => $sagaId, 'order_id' => $order->id]
        );

        // Mark existing transaction record as compensating.
        $transaction = SagaTransaction::where('saga_id', $sagaId)
            ->where('step', $step)
            ->first();

        $transaction?->markCompensating();

        $result = $stepInstance->compensate($payload);

        if ($result->isSuccess()) {
            $transaction?->markCompensated();

            Log::info("[Saga] Step {$step} compensated.", [
                'saga_id'  => $sagaId,
                'order_id' => $order->id,
            ]);
        } else {
            Log::error("[Saga] Compensation of step {$step} failed.", [
                'saga_id'  => $sagaId,
                'order_id' => $order->id,
                'error'    => $result->error,
            ]);
            // Continue compensating other steps despite partial failure.
        }
    }

    /**
     * Finalise a successfully completed saga.
     */
    private function completeSaga(string $sagaId, int $orderId): void
    {
        $order = Order::find($orderId);

        $order?->confirm();

        $state = $this->getSagaState($sagaId);
        $this->updateSagaState($sagaId, array_merge($state, [
            'status'       => 'completed',
            'completed_at' => now()->toIso8601String(),
        ]));

        $this->rabbitMQ->publishEvent('order.confirmed', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);

        Log::info('[Saga] Saga completed successfully.', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);
    }

    /**
     * Return the next step in the forward sequence, or null if none.
     */
    private function nextStep(string $currentStep): ?string
    {
        $index = array_search($currentStep, self::STEPS, true);

        if ($index === false) {
            return null;
        }

        $nextIndex = $index + 1;

        return isset(self::STEPS[$nextIndex]) ? self::STEPS[$nextIndex] : null;
    }

    /**
     * Resolve the SagaStep instance for a given step name.
     */
    private function resolveStep(string $step): SagaStep
    {
        return match ($step) {
            SagaTransaction::STEP_CREATE_ORDER      => $this->createOrderStep,
            SagaTransaction::STEP_RESERVE_INVENTORY => $this->reserveInventoryStep,
            SagaTransaction::STEP_PROCESS_PAYMENT   => $this->processPaymentStep,
            SagaTransaction::STEP_SEND_NOTIFICATION => $this->sendNotificationStep,
            default => throw new \InvalidArgumentException("Unknown saga step: {$step}"),
        };
    }

    /**
     * Create a unified step payload from order state and original order data.
     *
     * @param  array<string, mixed>  $orderData
     * @return array<string, mixed>
     */
    private function buildStepPayload(Order $order, array $orderData): array
    {
        return array_merge($orderData, [
            'saga_id'      => $order->saga_id,
            'order_id'     => $order->id,
            'tenant_id'    => $order->tenant_id,
            'customer_id'  => $order->customer_id,
            'total_amount' => $order->total_amount,
            'currency'     => $order->currency,
            'items'        => $order->items,
        ]);
    }

    /**
     * Persist a completed SagaTransaction record for a synchronous step.
     *
     * @param  array<string, mixed>  $result
     */
    private function recordStep(string $sagaId, int $orderId, string $step, array $result): void
    {
        SagaTransaction::create([
            'order_id'     => $orderId,
            'saga_id'      => $sagaId,
            'step'         => $step,
            'status'       => SagaTransaction::STATUS_COMPLETED,
            'payload'      => [],
            'result'       => $result,
            'started_at'   => now(),
            'completed_at' => now(),
        ]);
    }
}

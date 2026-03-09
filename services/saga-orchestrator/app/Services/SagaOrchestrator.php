<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Contracts\Repositories\SagaRepositoryInterface;
use App\Contracts\Saga\SagaDefinitionInterface;
use App\Contracts\Saga\SagaOrchestratorInterface;
use App\Domain\Saga\Models\SagaStep;
use App\Domain\Saga\Models\SagaTransaction;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Saga Orchestrator
 *
 * Coordinates distributed transactions across multiple microservices.
 * Implements the Saga Orchestration Pattern:
 *
 * 1. For each step: call the service endpoint via HTTP
 * 2. On success: mark step complete, proceed to next step
 * 3. On failure: mark step failed, trigger compensation (rollback) for all completed steps in REVERSE order
 *
 * This guarantees EVENTUAL CONSISTENCY across all participating microservices.
 *
 * Example flow (CreateOrder):
 * → create_order        [ORDER SERVICE]        ← success
 * → reserve_inventory   [INVENTORY SERVICE]    ← success
 * → process_payment     [PAYMENT SERVICE]      ← FAILURE
 * ← release_inventory   [INVENTORY SERVICE]    ← compensation
 * ← cancel_order        [ORDER SERVICE]        ← compensation
 */
class SagaOrchestrator implements SagaOrchestratorInterface
{
    /** @var array<string, SagaDefinitionInterface> */
    private array $definitions = [];

    public function __construct(
        private readonly SagaRepositoryInterface $sagaRepository,
        private readonly MessageBrokerInterface $messageBroker,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Register a saga definition.
     */
    public function registerDefinition(SagaDefinitionInterface $definition): void
    {
        $this->definitions[$definition->getType()] = $definition;
    }

    /**
     * {@inheritdoc}
     *
     * Starts the saga and executes steps sequentially.
     */
    public function start(string $sagaType, array $payload, string $tenantId): SagaTransaction
    {
        if (!isset($this->definitions[$sagaType])) {
            throw new \InvalidArgumentException("Saga type '{$sagaType}' is not registered.");
        }

        $sagaId = (string) Str::uuid();
        $payload['saga_id'] = $sagaId;
        $payload['tenant_id'] = $tenantId;

        // Create the saga record (durability)
        $saga = DB::transaction(function () use ($sagaType, $payload, $tenantId, $sagaId) {
            $saga = $this->sagaRepository->create([
                'id' => $sagaId,
                'tenant_id' => $tenantId,
                'saga_type' => $sagaType,
                'status' => SagaTransaction::STATUS_PENDING,
                'payload' => $payload,
                'retry_count' => 0,
                'started_at' => now(),
            ]);

            // Build and persist all steps upfront
            $steps = $this->definitions[$sagaType]->buildSteps($payload);

            foreach ($steps as $stepData) {
                $saga->steps()->create(array_merge($stepData, [
                    'status' => SagaStep::STATUS_PENDING,
                    'retry_count' => 0,
                ]));
            }

            return $saga;
        });

        $this->logger->info('Saga started', [
            'saga_id' => $sagaId,
            'saga_type' => $sagaType,
            'tenant_id' => $tenantId,
        ]);

        // Publish saga started event
        $this->messageBroker->publish('saga.started', [
            'saga_id' => $sagaId,
            'saga_type' => $sagaType,
            'tenant_id' => $tenantId,
            'timestamp' => now()->toISOString(),
        ], ['exchange' => 'saga.events', 'routing_key' => 'saga.started']);

        // Execute all steps
        $this->executeAll($saga);

        return $saga->fresh(['steps']);
    }

    /**
     * Execute all pending steps in order.
     */
    private function executeAll(SagaTransaction $saga): void
    {
        $saga->update(['status' => SagaTransaction::STATUS_RUNNING]);

        $pendingSteps = $saga->steps()
            ->where('status', SagaStep::STATUS_PENDING)
            ->orderBy('step_order')
            ->get();

        foreach ($pendingSteps as $step) {
            $success = $this->executeStep($saga, $step);

            if (!$success) {
                // Step failed - start compensation
                $this->compensate($saga, "Step '{$step->step_name}' failed: {$step->failure_reason}");
                return;
            }
        }

        // All steps completed successfully
        $saga->update([
            'status' => SagaTransaction::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $this->logger->info('Saga completed successfully', ['saga_id' => $saga->id]);

        $this->messageBroker->publish('saga.completed', [
            'saga_id' => $saga->id,
            'saga_type' => $saga->saga_type,
            'status' => 'completed',
        ], ['exchange' => 'saga.events', 'routing_key' => 'saga.completed']);
    }

    /**
     * Execute a single saga step by calling the service endpoint via HTTP.
     */
    private function executeStep(SagaTransaction $saga, SagaStep $step): bool
    {
        $step->update([
            'status' => SagaStep::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        $this->logger->info('Executing saga step', [
            'saga_id' => $saga->id,
            'step' => $step->step_name,
            'service' => $step->service,
        ]);

        $maxRetries = (int) config('saga.max_retries', 3);
        $attempt = 0;

        while ($attempt <= $maxRetries) {
            try {
                $client = new Client([
                    'timeout' => (int) config('saga.step_timeout', 30),
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'X-Tenant-ID' => $saga->tenant_id,
                        'X-Saga-ID' => $saga->id,
                        'X-Internal-Service' => 'saga-orchestrator',
                    ],
                ]);

                $response = $client->post($step->endpoint, [
                    'json' => $step->request_payload,
                ]);

                $responseData = json_decode((string) $response->getBody(), true) ?? [];

                // Mark step as completed
                $step->update([
                    'status' => SagaStep::STATUS_COMPLETED,
                    'response_payload' => $responseData,
                    'completed_at' => now(),
                    'retry_count' => $attempt,
                ]);

                $this->logger->info('Saga step completed', [
                    'saga_id' => $saga->id,
                    'step' => $step->step_name,
                    'response_status' => $response->getStatusCode(),
                ]);

                return true;

            } catch (RequestException $e) {
                $attempt++;
                $errorBody = $e->hasResponse()
                    ? (string) $e->getResponse()->getBody()
                    : $e->getMessage();

                $this->logger->warning('Saga step attempt failed', [
                    'saga_id' => $saga->id,
                    'step' => $step->step_name,
                    'attempt' => $attempt,
                    'error' => $errorBody,
                ]);

                if ($attempt > $maxRetries) {
                    $step->update([
                        'status' => SagaStep::STATUS_FAILED,
                        'failure_reason' => $errorBody,
                        'retry_count' => $attempt - 1,
                    ]);
                    return false;
                }

                // Exponential backoff before retry
                sleep(min(2 ** $attempt, 30));

            } catch (\Throwable $e) {
                $step->update([
                    'status' => SagaStep::STATUS_FAILED,
                    'failure_reason' => $e->getMessage(),
                    'retry_count' => $attempt,
                ]);

                $this->logger->error('Saga step failed with unexpected error', [
                    'saga_id' => $saga->id,
                    'step' => $step->step_name,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * Compensates (rolls back) all completed steps in REVERSE order.
     * This ensures transactional consistency across services.
     */
    public function compensate(SagaTransaction $saga, string $reason): void
    {
        $this->logger->warning('Saga compensation triggered', [
            'saga_id' => $saga->id,
            'reason' => $reason,
        ]);

        $saga->update([
            'status' => SagaTransaction::STATUS_COMPENSATING,
            'failure_reason' => $reason,
        ]);

        $this->messageBroker->publish('saga.compensation.started', [
            'saga_id' => $saga->id,
            'reason' => $reason,
        ], ['exchange' => 'saga.events', 'routing_key' => 'saga.compensation.started']);

        // Get completed steps in REVERSE order for rollback
        $completedSteps = $saga->steps()
            ->where('status', SagaStep::STATUS_COMPLETED)
            ->orderByDesc('step_order')
            ->get();

        $allCompensated = true;

        foreach ($completedSteps as $step) {
            if (empty($step->compensation_endpoint)) {
                // No compensation action needed (e.g., notification step)
                $step->update(['status' => SagaStep::STATUS_SKIPPED]);
                continue;
            }

            $compensated = $this->compensateStep($saga, $step);

            if (!$compensated) {
                $allCompensated = false;
                $this->logger->error('Compensation step failed - manual intervention required', [
                    'saga_id' => $saga->id,
                    'step' => $step->step_name,
                ]);
            }
        }

        $finalStatus = $allCompensated
            ? SagaTransaction::STATUS_COMPENSATED
            : SagaTransaction::STATUS_FAILED;

        $saga->update([
            'status' => $finalStatus,
            'completed_at' => now(),
        ]);

        $this->logger->info('Saga compensation completed', [
            'saga_id' => $saga->id,
            'status' => $finalStatus,
        ]);

        $this->messageBroker->publish('saga.compensation.completed', [
            'saga_id' => $saga->id,
            'status' => $finalStatus,
            'all_compensated' => $allCompensated,
        ], ['exchange' => 'saga.events', 'routing_key' => 'saga.compensation.completed']);
    }

    /**
     * Execute compensation (rollback) for a single step.
     */
    private function compensateStep(SagaTransaction $saga, SagaStep $step): bool
    {
        $step->update(['status' => SagaStep::STATUS_COMPENSATING]);

        $this->logger->info('Compensating saga step', [
            'saga_id' => $saga->id,
            'step' => $step->step_name,
        ]);

        try {
            $client = new Client([
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Tenant-ID' => $saga->tenant_id,
                    'X-Saga-ID' => $saga->id,
                    'X-Internal-Service' => 'saga-orchestrator',
                    'X-Compensation' => 'true',
                ],
            ]);

            // Replace URL placeholders with response data
            $compensationUrl = $this->buildCompensationUrl(
                $step->compensation_endpoint,
                $step->response_payload ?? []
            );

            // Use the step's original request payload for compensation
            $response = $client->post($compensationUrl, [
                'json' => array_merge($step->request_payload ?? [], [
                    'saga_id' => $saga->id,
                    'compensation' => true,
                ]),
            ]);

            $step->update([
                'status' => SagaStep::STATUS_COMPENSATED,
                'compensated_at' => now(),
            ]);

            $this->logger->info('Step compensated successfully', [
                'saga_id' => $saga->id,
                'step' => $step->step_name,
            ]);

            return true;

        } catch (\Throwable $e) {
            $step->update([
                'status' => SagaStep::STATUS_FAILED,
                'failure_reason' => 'Compensation failed: ' . $e->getMessage(),
            ]);

            $this->logger->error('Compensation step failed', [
                'saga_id' => $saga->id,
                'step' => $step->step_name,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Build compensation URL by replacing {placeholders} with response data.
     *
     * @param  array<string, mixed>  $responseData
     */
    private function buildCompensationUrl(string $url, array $responseData): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function (array $matches) use ($responseData) {
            return $responseData[$matches[1]] ?? $responseData['data'][$matches[1]] ?? $matches[0];
        }, $url);
    }

    /**
     * {@inheritdoc}
     */
    public function executeNextStep(SagaTransaction $saga): bool
    {
        $nextStep = $saga->steps()
            ->where('status', SagaStep::STATUS_PENDING)
            ->orderBy('step_order')
            ->first();

        if (!$nextStep) {
            return false;
        }

        return $this->executeStep($saga, $nextStep);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(string $sagaId): SagaTransaction
    {
        $saga = $this->sagaRepository->findById($sagaId);

        if (!$saga) {
            throw new \RuntimeException("Saga {$sagaId} not found.");
        }

        return $saga;
    }
}

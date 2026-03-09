<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Contracts\SagaOrchestratorInterface;
use App\Domain\Entities\SagaRecord;
use App\Infrastructure\Messaging\MessageBrokerFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Saga Orchestrator
 *
 * Central coordinator for distributed transactions across microservices.
 * Monitors saga state and triggers compensations when failures occur.
 */
class SagaOrchestrator implements SagaOrchestratorInterface
{
    public function __construct(
        protected readonly MessageBrokerFactory $brokerFactory
    ) {}

    public function getStatus(string $sagaId): ?array
    {
        $saga = SagaRecord::where('saga_id', $sagaId)->first();
        return $saga?->toArray();
    }

    public function compensate(string $sagaId, string $failedStep, array $context): void
    {
        Log::info("Saga Orchestrator: compensating saga {$sagaId}", [
            'failed_step' => $failedStep,
        ]);

        $saga = SagaRecord::where('saga_id', $sagaId)->first();
        $completedSteps = $saga?->completed_steps ?? [];

        // Execute compensations in reverse order
        foreach (array_reverse($completedSteps) as $step) {
            $this->executeCompensation($sagaId, $step, $context);
        }

        SagaRecord::updateOrCreate(
            ['saga_id' => $sagaId],
            ['status' => 'compensated', 'failed_step' => $failedStep]
        );

        $this->brokerFactory->getBroker()->publish('saga.compensated', [
            'saga_id' => $sagaId,
            'failed_step' => $failedStep,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function executeCompensation(string $sagaId, string $step, array $context): void
    {
        Log::info("Executing compensation for step: {$step}, saga: {$sagaId}");

        match ($step) {
            'reserve_stock' => $this->compensateStockReservation($sagaId, $context),
            'create_order' => $this->compensateOrderCreation($sagaId, $context),
            'process_payment' => $this->compensatePayment($sagaId, $context),
            default => Log::warning("No compensation handler for step: {$step}"),
        };
    }

    private function compensateStockReservation(string $sagaId, array $context): void
    {
        if (empty($context['reservations'])) {
            return;
        }

        foreach ($context['reservations'] as $productId => $reservationId) {
            try {
                Http::timeout(10)->delete(
                    config('services.inventory.url') . "/api/products/reservations/{$reservationId}"
                );
            } catch (\Exception $e) {
                Log::error("Failed to release reservation {$reservationId}: " . $e->getMessage());
            }
        }
    }

    private function compensateOrderCreation(string $sagaId, array $context): void
    {
        if (empty($context['order_id'])) {
            return;
        }

        try {
            Http::timeout(10)->post(
                config('services.order.url') . "/api/orders/{$context['order_id']}/cancel"
            );
        } catch (\Exception $e) {
            Log::error("Failed to cancel order {$context['order_id']}: " . $e->getMessage());
        }
    }

    private function compensatePayment(string $sagaId, array $context): void
    {
        // Stub: call payment service refund
        Log::info("Payment compensation for saga {$sagaId}");
    }
}

<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\Services\VehicleServiceWorkflowServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobStatusHistoryRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class VehicleServiceWorkflowService implements VehicleServiceWorkflowServiceInterface
{
    /** @var array<string, list<string>> */
    private const STATUS_TRANSITION_MATRIX = [
        'open' => ['inspection', 'diagnosis', 'in_progress', 'cancelled'],
        'inspection' => ['diagnosis', 'in_progress', 'cancelled'],
        'diagnosis' => ['waiting_approval', 'in_progress', 'cancelled'],
        'waiting_approval' => ['approved', 'cancelled'],
        'approved' => ['in_progress', 'waiting_parts', 'cancelled'],
        'waiting_parts' => ['in_progress', 'cancelled'],
        'in_progress' => ['quality_check', 'completed', 'cancelled'],
        'quality_check' => ['completed', 'rework', 'cancelled'],
        'rework' => ['quality_check', 'completed', 'cancelled'],
        'completed' => ['invoiced', 'closed', 'reversed'],
        'invoiced' => ['closed', 'reversed'],
        'closed' => ['reversed'],
        'cancelled' => ['reversed'],
        'reversed' => [],
    ];

    public function __construct(
        private readonly VehicleServiceJobCardRepositoryInterface $jobCardRepository,
        private readonly VehicleServiceJobStatusHistoryRepositoryInterface $statusHistoryRepository,
    ) {
    }

    public function transition(int|string $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById((int) $jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $targetStatus = strtolower(trim((string) ($payload['status'] ?? '')));
            if ($targetStatus === '') {
                return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'status is required.'));
            }

            $currentStatus = strtolower((string) $jobCard->get('status', 'open'));
            if (! $this->isAllowedTransition($currentStatus, $targetStatus)) {
                return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'Transition is not allowed.'));
            }

            $actorId = isset($payload['actor_id']) ? (int) $payload['actor_id'] : null;
            $tenantId = (int) $jobCard->get('tenant_id', 0);

            $fields = [
                'status' => $targetStatus,
                'updated_by' => $actorId,
            ];
            if ($targetStatus === 'completed') {
                $fields['completed_datetime'] = now()->toDateTimeString();
            }
            if ($targetStatus === 'cancelled') {
                $fields['cancelled_at'] = now()->toDateTimeString();
            }
            if ($targetStatus === 'reversed') {
                $fields['reversed_at'] = now()->toDateTimeString();
            }

            $updated = $this->jobCardRepository->update((int) $jobCardId, $fields);

            $this->statusHistoryRepository->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $jobCard->get('organization_unit_id'),
                'entity_type' => 'job_card',
                'entity_id' => (int) $jobCardId,
                'workflow_action' => 'transition',
                'from_status' => $currentStatus,
                'to_status' => $targetStatus,
                'reason' => $payload['reason'] ?? null,
                'changed_by' => $actorId,
                'changed_at' => now()->toDateTimeString(),
                'metadata' => [
                    'idempotency_key' => $payload['idempotency_key'] ?? null,
                ],
            ]);

            return Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function isAllowedTransition(string $fromStatus, string $toStatus): bool
    {
        if (! array_key_exists($fromStatus, self::STATUS_TRANSITION_MATRIX)) {
            return false;
        }

        return in_array($toStatus, self::STATUS_TRANSITION_MATRIX[$fromStatus], true);
    }
}

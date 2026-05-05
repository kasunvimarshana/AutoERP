<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\UpdateServiceTaskServiceInterface;
use Modules\Service\Domain\Entities\ServiceTask;
use Modules\Service\Domain\RepositoryInterfaces\ServiceTaskRepositoryInterface;
use RuntimeException;

class UpdateServiceTaskService extends BaseService implements UpdateServiceTaskServiceInterface
{
    public function __construct(private readonly ServiceTaskRepositoryInterface $taskRepository) {}

    protected function handle(array $data): ServiceTask
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->taskRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw new RuntimeException("Service task {$id} not found.");
        }

        if (in_array($existing->getStatus(), ['completed', 'cancelled'], true)) {
            throw new RuntimeException("Cannot update a {$existing->getStatus()} task.");
        }

        $updated = new ServiceTask(
            tenantId: $existing->getTenantId(),
            serviceWorkOrderId: $existing->getServiceWorkOrderId(),
            description: $data['description'] ?? $existing->getDescription(),
            orgUnitId: $existing->getOrgUnitId(),
            taskCode: $data['task_code'] ?? $existing->getTaskCode(),
            lineNumber: $existing->getLineNumber(),
            status: $data['status'] ?? $existing->getStatus(),
            assignedEmployeeId: isset($data['assigned_employee_id']) ? (int) $data['assigned_employee_id'] : $existing->getAssignedEmployeeId(),
            estimatedHours: isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : $existing->getEstimatedHours(),
            actualHours: isset($data['actual_hours']) ? (float) $data['actual_hours'] : $existing->getActualHours(),
            laborRate: isset($data['labor_rate']) ? (float) $data['labor_rate'] : $existing->getLaborRate(),
            laborAmount: isset($data['labor_amount']) ? (float) $data['labor_amount'] : $existing->getLaborAmount(),
            commissionAmount: isset($data['commission_amount']) ? (float) $data['commission_amount'] : $existing->getCommissionAmount(),
            incentiveAmount: isset($data['incentive_amount']) ? (float) $data['incentive_amount'] : $existing->getIncentiveAmount(),
            completedAt: $data['completed_at'] ?? $existing->getCompletedAt(),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        return $this->taskRepository->save($updated);
    }
}

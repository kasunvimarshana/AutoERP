<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\CreateServiceTaskServiceInterface;
use Modules\Service\Domain\Entities\ServiceTask;
use Modules\Service\Domain\RepositoryInterfaces\ServiceTaskRepositoryInterface;

class CreateServiceTaskService extends BaseService implements CreateServiceTaskServiceInterface
{
    public function __construct(private readonly ServiceTaskRepositoryInterface $taskRepository) {}

    protected function handle(array $data): ServiceTask
    {
        $lineNumber = $this->taskRepository->nextLineNumber(
            (int) $data['tenant_id'],
            (int) $data['service_work_order_id'],
        );

        $task = new ServiceTask(
            tenantId: (int) $data['tenant_id'],
            serviceWorkOrderId: (int) $data['service_work_order_id'],
            description: (string) $data['description'],
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            taskCode: $data['task_code'] ?? null,
            lineNumber: $lineNumber,
            status: 'pending',
            assignedEmployeeId: isset($data['assigned_employee_id']) ? (int) $data['assigned_employee_id'] : null,
            estimatedHours: isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : 0.0,
            actualHours: 0.0,
            laborRate: isset($data['labor_rate']) ? (float) $data['labor_rate'] : 0.0,
            laborAmount: 0.0,
            commissionAmount: 0.0,
            incentiveAmount: 0.0,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->taskRepository->save($task);
    }
}

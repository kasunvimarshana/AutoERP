<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Service\Domain\Entities\ServiceTask;
use Modules\Service\Domain\RepositoryInterfaces\ServiceTaskRepositoryInterface;
use Modules\Service\Infrastructure\Persistence\Eloquent\Models\ServiceTaskModel;

class EloquentServiceTaskRepository implements ServiceTaskRepositoryInterface
{
    public function __construct(private readonly ServiceTaskModel $model) {}

    public function findById(int $tenantId, int $id): ?ServiceTask
    {
        /** @var ServiceTaskModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $model !== null ? $this->mapModelToEntity($model) : null;
    }

    public function findByWorkOrder(int $tenantId, int $workOrderId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('service_work_order_id', $workOrderId)
            ->orderBy('line_number')
            ->get()
            ->map(fn (ServiceTaskModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function findByTenant(int $tenantId, ?int $orgUnitId = null, array $filters = []): array
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);

        if ($orgUnitId !== null) {
            $query->where('org_unit_id', $orgUnitId);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['service_work_order_id'])) {
            $query->where('service_work_order_id', (int) $filters['service_work_order_id']);
        }

        if (! empty($filters['assigned_employee_id'])) {
            $query->where('assigned_employee_id', (int) $filters['assigned_employee_id']);
        }

        return $query->orderBy('service_work_order_id')->orderBy('line_number')
            ->get()
            ->map(fn (ServiceTaskModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function nextLineNumber(int $tenantId, int $workOrderId): int
    {
        $count = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('service_work_order_id', $workOrderId)
            ->count();

        return $count + 1;
    }

    public function save(ServiceTask $task): ServiceTask
    {
        $payload = [
            'tenant_id' => $task->getTenantId(),
            'org_unit_id' => $task->getOrgUnitId(),
            'row_version' => $task->getRowVersion(),
            'service_work_order_id' => $task->getServiceWorkOrderId(),
            'line_number' => $task->getLineNumber(),
            'task_code' => $task->getTaskCode(),
            'description' => $task->getDescription(),
            'estimated_hours' => $task->getEstimatedHours(),
            'actual_hours' => $task->getActualHours(),
            'status' => $task->getStatus(),
            'assigned_employee_id' => $task->getAssignedEmployeeId(),
            'labor_rate' => $task->getLaborRate(),
            'labor_amount' => $task->getLaborAmount(),
            'commission_amount' => $task->getCommissionAmount(),
            'incentive_amount' => $task->getIncentiveAmount(),
            'completed_at' => $task->getCompletedAt(),
            'metadata' => $task->getMetadata(),
        ];

        $id = $task->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $task->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var ServiceTaskModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $task->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var ServiceTaskModel $saved */
            $saved = $this->model->newQuery()->create($payload);
        }

        return $this->mapModelToEntity($saved);
    }

    public function delete(int $tenantId, int $id): bool
    {
        return (bool) $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->delete();
    }

    private function mapModelToEntity(ServiceTaskModel $model): ServiceTask
    {
        return new ServiceTask(
            tenantId: (int) $model->tenant_id,
            serviceWorkOrderId: (int) $model->service_work_order_id,
            description: (string) $model->description,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            taskCode: $model->task_code,
            lineNumber: (int) $model->line_number,
            status: (string) $model->status,
            assignedEmployeeId: $model->assigned_employee_id !== null ? (int) $model->assigned_employee_id : null,
            estimatedHours: (float) $model->estimated_hours,
            actualHours: (float) $model->actual_hours,
            laborRate: (float) $model->labor_rate,
            laborAmount: (float) $model->labor_amount,
            commissionAmount: (float) $model->commission_amount,
            incentiveAmount: (float) $model->incentive_amount,
            completedAt: $model->completed_at !== null ? (string) $model->completed_at : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at !== null ? new \DateTimeImmutable((string) $model->created_at) : null,
            updatedAt: $model->updated_at !== null ? new \DateTimeImmutable((string) $model->updated_at) : null,
            id: (int) $model->id,
        );
    }
}

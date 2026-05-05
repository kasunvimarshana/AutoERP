<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Service\Domain\Entities\ServiceLaborEntry;
use Modules\Service\Domain\RepositoryInterfaces\ServiceLaborEntryRepositoryInterface;
use Modules\Service\Infrastructure\Persistence\Eloquent\Models\ServiceLaborEntryModel;

class EloquentServiceLaborEntryRepository implements ServiceLaborEntryRepositoryInterface
{
    public function __construct(private readonly ServiceLaborEntryModel $model) {}

    public function findById(int $tenantId, int $id): ?ServiceLaborEntry
    {
        /** @var ServiceLaborEntryModel|null $model */
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
            ->orderBy('started_at')
            ->get()
            ->map(fn (ServiceLaborEntryModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function findByEmployee(int $tenantId, int $employeeId, array $filters = []): array
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('started_at')
            ->get()
            ->map(fn (ServiceLaborEntryModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(ServiceLaborEntry $entry): ServiceLaborEntry
    {
        $payload = [
            'tenant_id' => $entry->getTenantId(),
            'org_unit_id' => $entry->getOrgUnitId(),
            'row_version' => $entry->getRowVersion(),
            'service_work_order_id' => $entry->getServiceWorkOrderId(),
            'service_task_id' => $entry->getServiceTaskId(),
            'employee_id' => $entry->getEmployeeId(),
            'started_at' => $entry->getStartedAt(),
            'ended_at' => $entry->getEndedAt(),
            'hours_worked' => $entry->getHoursWorked(),
            'labor_rate' => $entry->getLaborRate(),
            'labor_amount' => $entry->getLaborAmount(),
            'commission_rate' => $entry->getCommissionRate(),
            'commission_amount' => $entry->getCommissionAmount(),
            'incentive_amount' => $entry->getIncentiveAmount(),
            'status' => $entry->getStatus(),
            'metadata' => $entry->getMetadata(),
        ];

        $id = $entry->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $entry->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var ServiceLaborEntryModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $entry->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var ServiceLaborEntryModel $saved */
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

    private function mapModelToEntity(ServiceLaborEntryModel $model): ServiceLaborEntry
    {
        return new ServiceLaborEntry(
            tenantId: (int) $model->tenant_id,
            serviceWorkOrderId: (int) $model->service_work_order_id,
            employeeId: (int) $model->employee_id,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            serviceTaskId: $model->service_task_id !== null ? (int) $model->service_task_id : null,
            startedAt: $model->started_at !== null ? (string) $model->started_at : null,
            endedAt: $model->ended_at !== null ? (string) $model->ended_at : null,
            hoursWorked: (float) $model->hours_worked,
            laborRate: (float) $model->labor_rate,
            laborAmount: (float) $model->labor_amount,
            commissionRate: (float) $model->commission_rate,
            commissionAmount: (float) $model->commission_amount,
            incentiveAmount: (float) $model->incentive_amount,
            status: (string) $model->status,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at !== null ? new \DateTimeImmutable((string) $model->created_at) : null,
            updatedAt: $model->updated_at !== null ? new \DateTimeImmutable((string) $model->updated_at) : null,
            id: (int) $model->id,
        );
    }
}

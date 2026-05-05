<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Service\Domain\Entities\ServiceMaintenancePlan;
use Modules\Service\Domain\RepositoryInterfaces\ServiceMaintenancePlanRepositoryInterface;
use Modules\Service\Infrastructure\Persistence\Eloquent\Models\ServiceMaintenancePlanModel;

class EloquentServiceMaintenancePlanRepository implements ServiceMaintenancePlanRepositoryInterface
{
    public function __construct(
        private readonly ServiceMaintenancePlanModel $model,
    ) {}

    public function save(ServiceMaintenancePlan $plan): ServiceMaintenancePlan
    {
        if ($plan->getId() !== null) {
            /** @var ServiceMaintenancePlanModel $record */
            $record = $this->model->newQuery()->findOrFail($plan->getId());
            $record->update($this->toArray($plan));
            $record->refresh();
        } else {
            /** @var ServiceMaintenancePlanModel $record */
            $record = $this->model->newQuery()->create($this->toArray($plan));
        }

        return $this->mapToEntity($record);
    }

    public function findById(int $tenantId, int $id): ?ServiceMaintenancePlan
    {
        /** @var ServiceMaintenancePlanModel|null $record */
        $record = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $record !== null ? $this->mapToEntity($record) : null;
    }

    public function findByCode(int $tenantId, string $planCode): ?ServiceMaintenancePlan
    {
        /** @var ServiceMaintenancePlanModel|null $record */
        $record = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('plan_code', $planCode)
            ->first();

        return $record !== null ? $this->mapToEntity($record) : null;
    }

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);

        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => array_map(
                fn (ServiceMaintenancePlanModel $m): ServiceMaintenancePlan => $this->mapToEntity($m),
                $paginator->items()
            ),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    public function existsByCode(int $tenantId, string $planCode): bool
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('plan_code', $planCode)
            ->exists();
    }

    private function toArray(ServiceMaintenancePlan $plan): array
    {
        return [
            'tenant_id' => $plan->getTenantId(),
            'org_unit_id' => $plan->getOrgUnitId(),
            'row_version' => $plan->getRowVersion(),
            'plan_code' => $plan->getPlanCode(),
            'plan_name' => $plan->getPlanName(),
            'description' => $plan->getDescription(),
            'asset_id' => $plan->getAssetId(),
            'product_id' => $plan->getProductId(),
            'trigger_type' => $plan->getTriggerType(),
            'interval_days' => $plan->getIntervalDays(),
            'interval_km' => $plan->getIntervalKm(),
            'interval_hours' => $plan->getIntervalHours(),
            'advance_notice_days' => $plan->getAdvanceNoticeDays(),
            'last_serviced_at' => $plan->getLastServicedAt(),
            'next_service_due_at' => $plan->getNextServiceDueAt(),
            'last_service_odometer' => $plan->getLastServiceOdometer(),
            'next_service_odometer' => $plan->getNextServiceOdometer(),
            'assigned_employee_id' => $plan->getAssignedEmployeeId(),
            'status' => $plan->getStatus(),
            'metadata' => $plan->getMetadata(),
        ];
    }

    private function mapToEntity(ServiceMaintenancePlanModel $model): ServiceMaintenancePlan
    {
        return new ServiceMaintenancePlan(
            tenantId: (int) $model->tenant_id,
            planCode: (string) $model->plan_code,
            planName: (string) $model->plan_name,
            triggerType: (string) $model->trigger_type,
            status: (string) $model->status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            assetId: $model->asset_id !== null ? (int) $model->asset_id : null,
            productId: $model->product_id !== null ? (int) $model->product_id : null,
            assignedEmployeeId: $model->assigned_employee_id !== null ? (int) $model->assigned_employee_id : null,
            description: $model->description,
            intervalDays: $model->interval_days !== null ? (int) $model->interval_days : null,
            intervalKm: $model->interval_km !== null ? (string) $model->interval_km : null,
            intervalHours: $model->interval_hours !== null ? (string) $model->interval_hours : null,
            advanceNoticeDays: $model->advance_notice_days !== null ? (int) $model->advance_notice_days : null,
            lastServicedAt: $model->last_serviced_at,
            nextServiceDueAt: $model->next_service_due_at,
            lastServiceOdometer: $model->last_service_odometer !== null ? (string) $model->last_service_odometer : null,
            nextServiceOdometer: $model->next_service_odometer !== null ? (string) $model->next_service_odometer : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            id: (int) $model->id,
        );
    }
}

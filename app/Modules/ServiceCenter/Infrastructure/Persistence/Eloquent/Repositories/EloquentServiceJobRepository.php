<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\ServiceCenter\Domain\Entities\ServiceJob;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceJobRepositoryInterface;
use Modules\ServiceCenter\Domain\ValueObjects\JobType;
use Modules\ServiceCenter\Domain\ValueObjects\ServiceJobStatus;
use Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Models\ServiceJobModel;

class EloquentServiceJobRepository implements ServiceJobRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?ServiceJob
    {
        $model = ServiceJobModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenant(int $tenantId, array $filters = []): array
    {
        $query = ServiceJobModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', (int) $filters['vehicle_id']);
        }
        if (!empty($filters['job_type'])) {
            $query->where('job_type', $filters['job_type']);
        }

        return $query->orderByDesc('id')->get()->map(fn ($m) => $this->toEntity($m))->all();
    }

    public function findByVehicle(int $vehicleId, int $tenantId): array
    {
        return ServiceJobModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function save(ServiceJob $serviceJob): ServiceJob
    {
        $data = [
            'tenant_id'         => $serviceJob->tenantId,
            'org_unit_id'       => $serviceJob->orgUnitId,
            'vehicle_id'        => $serviceJob->vehicleId,
            'driver_id'         => $serviceJob->driverId,
            'job_number'        => $serviceJob->jobNumber,
            'job_type'          => $serviceJob->jobType->value,
            'status'            => $serviceJob->status->value,
            'scheduled_at'      => $serviceJob->scheduledAt->format('Y-m-d H:i:s'),
            'started_at'        => $serviceJob->startedAt?->format('Y-m-d H:i:s'),
            'completed_at'      => $serviceJob->completedAt?->format('Y-m-d H:i:s'),
            'odometer_in'       => $serviceJob->odometerIn,
            'odometer_out'      => $serviceJob->odometerOut,
            'description'       => $serviceJob->description,
            'parts_cost'        => $serviceJob->partsCost,
            'labour_cost'       => $serviceJob->labourCost,
            'total_cost'        => $serviceJob->totalCost,
            'technician_notes'  => $serviceJob->technicianNotes,
            'customer_approval' => $serviceJob->customerApproval,
            'metadata'          => $serviceJob->metadata,
            'is_active'         => $serviceJob->isActive,
        ];

        if ($serviceJob->id === null) {
            $data['row_version'] = 1;
            $model = ServiceJobModel::create($data);
        } else {
            $model = ServiceJobModel::withoutGlobalScope('tenant')->findOrFail($serviceJob->id);
            $model->update($data);
            $model->increment('row_version');
            $model->refresh();
        }

        return $this->toEntity($model);
    }

    public function updateStatus(int $id, int $tenantId, ServiceJobStatus $status): void
    {
        ServiceJobModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['status' => $status->value]);
    }

    public function delete(int $id, int $tenantId): void
    {
        ServiceJobModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->delete();
    }

    private function toEntity(ServiceJobModel $model): ServiceJob
    {
        return new ServiceJob(
            id: $model->id,
            tenantId: (int) $model->tenant_id,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            vehicleId: (int) $model->vehicle_id,
            driverId: $model->driver_id !== null ? (int) $model->driver_id : null,
            jobNumber: $model->job_number,
            jobType: JobType::from($model->job_type),
            status: ServiceJobStatus::from($model->status),
            scheduledAt: new DateTimeImmutable($model->scheduled_at->format('Y-m-d H:i:s')),
            startedAt: $model->started_at !== null ? new DateTimeImmutable($model->started_at->format('Y-m-d H:i:s')) : null,
            completedAt: $model->completed_at !== null ? new DateTimeImmutable($model->completed_at->format('Y-m-d H:i:s')) : null,
            odometerIn: $model->odometer_in !== null ? (string) $model->odometer_in : null,
            odometerOut: $model->odometer_out !== null ? (string) $model->odometer_out : null,
            description: $model->description,
            partsCost: (string) $model->parts_cost,
            labourCost: (string) $model->labour_cost,
            totalCost: (string) $model->total_cost,
            technicianNotes: $model->technician_notes,
            customerApproval: (bool) $model->customer_approval,
            metadata: $model->metadata,
            isActive: (bool) $model->is_active,
            rowVersion: (int) $model->row_version,
        );
    }
}

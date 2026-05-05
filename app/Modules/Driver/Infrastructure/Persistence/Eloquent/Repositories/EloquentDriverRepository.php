<?php

declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Driver\Domain\Entities\Driver;
use Modules\Driver\Domain\RepositoryInterfaces\DriverRepositoryInterface;
use Modules\Driver\Domain\ValueObjects\CompensationType;
use Modules\Driver\Domain\ValueObjects\DriverStatus;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Models\DriverModel;

class EloquentDriverRepository implements DriverRepositoryInterface
{
    public function findById(int $id): ?Driver
    {
        $model = DriverModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenant(int $tenantId, ?int $orgUnitId = null): array
    {
        $query = DriverModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);

        if ($orgUnitId !== null) {
            $query->where('org_unit_id', $orgUnitId);
        }

        return $query->get()->map(fn ($m) => $this->toEntity($m))->all();
    }

    public function findAvailableForTrip(int $tenantId, ?int $orgUnitId = null): array
    {
        $query = DriverModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', DriverStatus::Available->value)
            ->where('is_active', true);

        if ($orgUnitId !== null) {
            $query->where('org_unit_id', $orgUnitId);
        }

        return $query->get()->map(fn ($m) => $this->toEntity($m))->all();
    }

    public function save(Driver $driver): Driver
    {
        $data = [
            'tenant_id'         => $driver->tenantId,
            'org_unit_id'       => $driver->orgUnitId,
            'employee_id'       => $driver->employeeId,
            'driver_code'       => $driver->driverCode,
            'full_name'         => $driver->fullName,
            'phone'             => $driver->phone,
            'email'             => $driver->email,
            'address'           => $driver->address,
            'compensation_type' => $driver->compensationType->value,
            'per_trip_rate'     => $driver->perTripRate,
            'commission_pct'    => $driver->commissionPct,
            'status'            => $driver->status->value,
            'metadata'          => $driver->metadata,
            'is_active'         => $driver->isActive,
        ];

        if ($driver->id === null) {
            $data['row_version'] = 1;
            $model = DriverModel::create($data);
        } else {
            $model = DriverModel::withoutGlobalScope('tenant')->findOrFail($driver->id);
            $model->update($data);
            $model->increment('row_version');
            $model->refresh();
        }

        return $this->toEntity($model);
    }

    public function updateStatus(int $id, DriverStatus $status): void
    {
        DriverModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->update(['status' => $status->value]);
    }

    public function delete(int $id): void
    {
        DriverModel::withoutGlobalScope('tenant')->where('id', $id)->delete();
    }

    private function toEntity(DriverModel $model): Driver
    {
        return new Driver(
            id: $model->id,
            tenantId: (int) $model->tenant_id,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            employeeId: $model->employee_id !== null ? (int) $model->employee_id : null,
            driverCode: $model->driver_code,
            fullName: $model->full_name,
            phone: $model->phone,
            email: $model->email,
            address: $model->address,
            compensationType: CompensationType::from($model->compensation_type),
            perTripRate: (string) $model->per_trip_rate,
            commissionPct: (string) $model->commission_pct,
            status: DriverStatus::from($model->status),
            metadata: $model->metadata,
            isActive: (bool) $model->is_active,
        );
    }
}

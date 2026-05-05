<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Fleet\Domain\Entities\VehicleType;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleTypeRepositoryInterface;
use Modules\Fleet\Infrastructure\Persistence\Eloquent\Models\VehicleTypeModel;

class EloquentVehicleTypeRepository implements VehicleTypeRepositoryInterface
{
    public function find(int $id): ?VehicleType
    {
        $model = VehicleTypeModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function listByTenant(int $tenantId): array
    {
        return VehicleTypeModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function save(VehicleType $type): VehicleType
    {
        $data = [
            'tenant_id'        => $type->tenantId,
            'org_unit_id'      => $type->orgUnitId,
            'name'             => $type->name,
            'description'      => $type->description,
            'base_daily_rate'  => $type->baseDailyRate,
            'base_hourly_rate' => $type->baseHourlyRate,
            'seating_capacity' => $type->seatingCapacity,
            'is_active'        => $type->isActive,
        ];

        if ($type->id !== null) {
            $model = VehicleTypeModel::findOrFail($type->id);
            $model->increment('row_version');
            $model->update($data);
        } else {
            $model = VehicleTypeModel::create($data);
        }

        return $this->toEntity($model->fresh());
    }

    public function delete(int $id): void
    {
        VehicleTypeModel::findOrFail($id)->delete();
    }

    private function toEntity(VehicleTypeModel $m): VehicleType
    {
        return new VehicleType(
            tenantId:        $m->tenant_id,
            name:            $m->name,
            description:     $m->description,
            baseDailyRate:   (string) $m->base_daily_rate,
            baseHourlyRate:  (string) $m->base_hourly_rate,
            seatingCapacity: $m->seating_capacity,
            isActive:        (bool) $m->is_active,
            orgUnitId:       $m->org_unit_id,
            id:              $m->id,
        );
    }
}

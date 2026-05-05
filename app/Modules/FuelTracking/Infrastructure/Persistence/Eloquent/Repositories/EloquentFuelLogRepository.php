<?php

declare(strict_types=1);

namespace Modules\FuelTracking\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\FuelTracking\Domain\Entities\FuelLog;
use Modules\FuelTracking\Domain\RepositoryInterfaces\FuelLogRepositoryInterface;
use Modules\FuelTracking\Domain\ValueObjects\FuelType;
use Modules\FuelTracking\Infrastructure\Persistence\Eloquent\Models\FuelLogModel;

class EloquentFuelLogRepository implements FuelLogRepositoryInterface
{
    public function findById(string $id): ?FuelLog
    {
        $model = FuelLogModel::withoutGlobalScope('tenant')->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenant(string $tenantId, string $orgUnitId): array
    {
        return FuelLogModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('org_unit_id', $orgUnitId)
            ->orderByDesc('filled_at')
            ->get()
            ->map(fn (FuelLogModel $m) => $this->toEntity($m))
            ->all();
    }

    public function findByVehicle(string $tenantId, string $vehicleId): array
    {
        return FuelLogModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->orderByDesc('filled_at')
            ->get()
            ->map(fn (FuelLogModel $m) => $this->toEntity($m))
            ->all();
    }

    public function findByDriver(string $tenantId, string $driverId): array
    {
        return FuelLogModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('driver_id', $driverId)
            ->orderByDesc('filled_at')
            ->get()
            ->map(fn (FuelLogModel $m) => $this->toEntity($m))
            ->all();
    }

    public function save(FuelLog $log): FuelLog
    {
        $model = FuelLogModel::withoutGlobalScope('tenant')->firstOrNew(['id' => $log->id]);

        $isNew = ! $model->exists;

        $model->fill([
            'id'               => $log->id,
            'tenant_id'        => $log->tenantId,
            'org_unit_id'      => $log->orgUnitId,
            'row_version'      => $log->rowVersion,
            'log_number'       => $log->logNumber,
            'vehicle_id'       => $log->vehicleId,
            'driver_id'        => $log->driverId,
            'fuel_type'        => $log->fuelType->value,
            'odometer_reading' => $log->odoReading,
            'litres'           => $log->litres,
            'cost_per_litre'   => $log->costPerLitre,
            'total_cost'       => $log->totalCost,
            'station_name'     => $log->stationName,
            'filled_at'        => $log->filledAt?->format('Y-m-d H:i:s'),
            'notes'            => $log->notes,
            'metadata'         => $log->metadata,
            'is_active'        => $log->isActive,
        ]);

        $model->save();

        if (! $isNew) {
            $model->increment('row_version');
        }

        return $this->toEntity($model->fresh());
    }

    public function delete(string $id): void
    {
        FuelLogModel::withoutGlobalScope('tenant')->where('id', $id)->delete();
    }

    private function toEntity(FuelLogModel $model): FuelLog
    {
        return new FuelLog(
            id: $model->id,
            tenantId: (string) $model->tenant_id,
            orgUnitId: (string) $model->org_unit_id,
            rowVersion: (int) $model->row_version,
            logNumber: $model->log_number,
            vehicleId: $model->vehicle_id,
            driverId: $model->driver_id,
            fuelType: FuelType::from($model->fuel_type),
            odoReading: number_format((float) $model->odometer_reading, 2, '.', ''),
            litres: number_format((float) $model->litres, 6, '.', ''),
            costPerLitre: number_format((float) $model->cost_per_litre, 6, '.', ''),
            totalCost: number_format((float) $model->total_cost, 6, '.', ''),
            stationName: $model->station_name,
            filledAt: $model->filled_at
                ? new DateTimeImmutable($model->filled_at->format('Y-m-d H:i:s'))
                : null,
            notes: $model->notes,
            metadata: $model->metadata,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at
                ? new DateTimeImmutable($model->created_at->format('Y-m-d H:i:s'))
                : null,
            updatedAt: $model->updated_at
                ? new DateTimeImmutable($model->updated_at->format('Y-m-d H:i:s'))
                : null,
        );
    }
}

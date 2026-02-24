<?php

namespace Modules\Fleet\Infrastructure\Repositories;

use Modules\Fleet\Domain\Contracts\MaintenanceRecordRepositoryInterface;
use Modules\Fleet\Infrastructure\Models\MaintenanceRecordModel;

class MaintenanceRecordRepository implements MaintenanceRecordRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return MaintenanceRecordModel::find($id);
    }

    public function findByVehicle(string $vehicleId): iterable
    {
        return MaintenanceRecordModel::where('vehicle_id', $vehicleId)->orderByDesc('performed_at')->get();
    }

    public function create(array $data): object
    {
        return MaintenanceRecordModel::create($data);
    }

    public function delete(string $id): void
    {
        MaintenanceRecordModel::findOrFail($id)->delete();
    }
}

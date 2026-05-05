<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Asset\Domain\Entities\Vehicle;
use Modules\Asset\Domain\RepositoryInterfaces\VehicleRepositoryInterface;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\VehicleModel;

class EloquentVehicleRepository implements VehicleRepositoryInterface
{
    public function create(Vehicle $vehicle): void
    {
        VehicleModel::create([
            'id' => $vehicle->getId(),
            'tenant_id' => $vehicle->getTenantId(),
            'asset_id' => $vehicle->getAssetId(),
            'vin' => $vehicle->getVin(),
            'registration_plate' => $vehicle->getRegistrationPlate(),
            'vehicle_type' => $vehicle->getVehicleType(),
            'make' => $vehicle->getMake(),
            'model' => $vehicle->getModel(),
            'year' => $vehicle->getYear(),
            'current_mileage' => $vehicle->getCurrentMileage(),
            'status' => $vehicle->getStatus(),
        ]);
    }

    public function findById(string $id): ?Vehicle
    {
        $model = VehicleModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByVin(string $vin): ?Vehicle
    {
        $model = VehicleModel::where('vin', $vin)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByRegistrationPlate(string $registrationPlate): ?Vehicle
    {
        $model = VehicleModel::where('registration_plate', $registrationPlate)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function getAllByTenant(
        string $tenantId,
        array $filters = [],
        int $page = 1,
        int $limit = 50,
    ): array {
        $query = VehicleModel::byTenant($tenantId);

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }
        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function getAvailableForRental(
        string $tenantId,
        int $page = 1,
        int $limit = 50,
    ): array {
        $query = VehicleModel::availableForRental($tenantId);
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function getByStatus(
        string $tenantId,
        string $status,
        int $page = 1,
        int $limit = 50,
    ): array {
        $query = VehicleModel::byTenant($tenantId)->byStatus($status);
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function getByType(
        string $tenantId,
        string $vehicleType,
        int $page = 1,
        int $limit = 50,
    ): array {
        $query = VehicleModel::byTenant($tenantId)->byType($vehicleType);
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function update(Vehicle $vehicle): void
    {
        VehicleModel::where('id', $vehicle->getId())->update([
            'status' => $vehicle->getStatus(),
            'current_mileage' => $vehicle->getCurrentMileage(),
            'current_location_id' => $vehicle->getCurrentLocationId(),
        ]);
    }

    public function delete(string $id): void
    {
        VehicleModel::where('id', $id)->delete();
    }

    public function countAvailableForRental(string $tenantId): int
    {
        return VehicleModel::availableForRental($tenantId)->count();
    }

    public function vinExists(string $tenantId, string $vin): bool
    {
        return VehicleModel::byTenant($tenantId)
            ->where('vin', $vin)
            ->exists();
    }

    public function registrationPlateExists(string $tenantId, string $registrationPlate): bool
    {
        return VehicleModel::byTenant($tenantId)
            ->where('registration_plate', $registrationPlate)
            ->exists();
    }

    private function toDomain(VehicleModel $model): Vehicle
    {
        return new Vehicle(
            $model->id,
            $model->tenant_id,
            $model->asset_id,
            $model->vin,
            $model->registration_plate,
            $model->vehicle_type,
            $model->make,
            $model->model,
            $model->year,
            $model->color,
            $model->fuel_type,
            $model->transmission,
            $model->seating_capacity,
            $model->fuel_tank_capacity,
            $model->engine_displacement,
            $model->current_mileage,
            $model->current_location_id,
            $model->is_rentable,
            $model->is_serviceable,
            $model->status,
            $model->insurance_policy_number,
            $model->insurance_expiry_date,
            $model->last_service_date,
            $model->next_service_date,
            $model->next_service_mileage,
        );
    }
}

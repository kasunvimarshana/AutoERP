<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Vehicle\Domain\Entities\Vehicle;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRepositoryInterface;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleJobCardModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleRentalModel;

class EloquentVehicleRepository extends EloquentRepository implements VehicleRepositoryInterface
{
    public function __construct(
        VehicleModel $model,
        private readonly VehicleRentalModel $rentalModel,
        private readonly VehicleJobCardModel $jobCardModel,
    ) {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (VehicleModel $record): Vehicle => $this->toVehicleEntity($record));
    }

    public function save(Vehicle $vehicle): Vehicle
    {
        $data = [
            'tenant_id' => $vehicle->getTenantId(),
            'ownership_type' => $vehicle->getOwnershipType(),
            'make' => $vehicle->getMake(),
            'model' => $vehicle->getModel(),
            'vin' => $vehicle->getVin(),
            'registration_number' => $vehicle->getRegistrationNumber(),
            'chassis_number' => $vehicle->getChassisNumber(),
            'rental_status' => $vehicle->getRentalStatus(),
            'service_status' => $vehicle->getServiceStatus(),
            'next_maintenance_due_at' => $vehicle->getNextMaintenanceDueAt(),
        ];

        $saved = $vehicle->getId() !== null
            ? $this->update($vehicle->getId(), $data)
            : $this->create($data);

        /** @var VehicleModel $saved */

        return $this->toDomainEntity($saved);
    }

    public function findByTenantAndVin(int $tenantId, string $vin): ?Vehicle
    {
        /** @var VehicleModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('vin', $vin)
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function existsActiveRental(int $tenantId, int $vehicleId): bool
    {
        return $this->rentalModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('rental_status', ['reserved', 'active'])
            ->exists();
    }

    public function existsOpenJobCard(int $tenantId, int $vehicleId): bool
    {
        return $this->jobCardModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('workflow_status', ['draft', 'scheduled', 'in_progress', 'awaiting_parts', 'quality_check'])
            ->exists();
    }

    private function toVehicleEntity(VehicleModel $model): Vehicle
    {
        return new Vehicle(
            tenantId: (int) $model->tenant_id,
            ownershipType: (string) $model->ownership_type,
            make: (string) $model->make,
            model: (string) $model->model,
            rentalStatus: (string) $model->rental_status,
            serviceStatus: (string) $model->service_status,
            id: (int) $model->id,
            vin: $model->vin,
            registrationNumber: $model->registration_number,
            chassisNumber: $model->chassis_number,
            nextMaintenanceDueAt: $model->next_maintenance_due_at?->format('Y-m-d H:i:s'),
        );
    }
}

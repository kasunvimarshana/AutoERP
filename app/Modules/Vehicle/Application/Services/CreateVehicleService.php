<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Vehicle\Application\Contracts\CreateVehicleServiceInterface;
use Modules\Vehicle\Domain\Entities\Vehicle;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRepositoryInterface;

class CreateVehicleService extends BaseService implements CreateVehicleServiceInterface
{
    public function __construct(private readonly VehicleRepositoryInterface $vehicleRepository)
    {
        parent::__construct($vehicleRepository);
    }

    protected function handle(array $data): Vehicle
    {
        $vehicle = new Vehicle(
            tenantId: (int) $data['tenant_id'],
            ownershipType: (string) $data['ownership_type'],
            make: (string) $data['make'],
            model: (string) $data['model'],
            rentalStatus: (string) ($data['rental_status'] ?? 'available'),
            serviceStatus: (string) ($data['service_status'] ?? 'none'),
            vin: $data['vin'] ?? null,
            registrationNumber: $data['registration_number'] ?? null,
            chassisNumber: $data['chassis_number'] ?? null,
            nextMaintenanceDueAt: $data['next_maintenance_due_at'] ?? null,
        );

        /** @var Vehicle $saved */
        $saved = $this->vehicleRepository->save($vehicle);

        $this->vehicleRepository->update((int) $saved->getId(), [
            'org_unit_id' => $data['org_unit_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'asset_code' => $data['asset_code'] ?? null,
            'year' => $data['year'] ?? null,
            'fuel_type' => $data['fuel_type'] ?? 'petrol',
            'transmission' => $data['transmission'] ?? 'manual',
            'odometer' => $data['odometer'] ?? '0.000000',
            'primary_image_path' => $data['primary_image_path'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        /** @var Vehicle $fresh */
        $fresh = $this->vehicleRepository->find((int) $saved->getId());

        return $fresh;
    }
}

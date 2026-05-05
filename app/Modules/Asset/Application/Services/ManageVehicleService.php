<?php declare(strict_types=1);

namespace Modules\Asset\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Asset\Application\Contracts\ManageVehicleServiceInterface;
use Modules\Asset\Domain\Entities\Vehicle;
use Modules\Asset\Domain\RepositoryInterfaces\VehicleRepositoryInterface;

class ManageVehicleService implements ManageVehicleServiceInterface
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicles,
    ) {}

    public function create(array $data): Vehicle
    {
        return DB::transaction(function () use ($data): Vehicle {
            $vehicle = new Vehicle(
                id: Str::uuid()->toString(),
                tenantId: (string) $data['tenant_id'],
                assetId: $data['asset_id'],
                registrationNumber: $data['registration_number'],
                make: $data['make'],
                model: $data['model'],
                year: (int) $data['year'],
                vin: $data['vin'] ?? null,
                fuelType: $data['fuel_type'] ?? 'gasoline',
                currentMileage: (int) $data['current_mileage'],
                status: $data['status'] ?? 'active',
            );

            $this->vehicles->create($vehicle);
            return $vehicle;
        });
    }

    public function update(int $tenantId, string $id, array $data): Vehicle
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Vehicle {
            $vehicle = $this->vehicles->findById($id);
            if (!$vehicle || $vehicle->getTenantId() !== (string) $tenantId) {
                throw new \Exception('Vehicle not found');
            }

            $this->vehicles->update($vehicle);
            return $this->vehicles->findById($id);
        });
    }

    public function find(int $tenantId, string $id): Vehicle
    {
        $vehicle = $this->vehicles->findById($id);
        if (!$vehicle || $vehicle->getTenantId() !== (string) $tenantId) {
            throw new \Exception('Vehicle not found');
        }
        return $vehicle;
    }

    public function delete(int $tenantId, string $id): void
    {
        $this->find($tenantId, $id);
        $this->vehicles->delete($id);
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): array
    {
        return $this->vehicles->getAllByTenant((string) $tenantId, $page, $perPage);
    }

    public function getAvailableForRental(int $tenantId): array
    {
        return $this->vehicles->getAvailableForRental((string) $tenantId);
    }

    public function updateStatus(int $tenantId, string $id, string $status): Vehicle
    {
        return DB::transaction(function () use ($tenantId, $id, $status): Vehicle {
            $vehicle = $this->find($tenantId, $id);
            $vehicle->updateStatus($status);
            $this->vehicles->update($vehicle);
            return $this->vehicles->findById($id);
        });
    }

    public function updateMileage(int $tenantId, string $id, int $mileage): Vehicle
    {
        return DB::transaction(function () use ($tenantId, $id, $mileage): Vehicle {
            $vehicle = $this->find($tenantId, $id);
            $vehicle->updateMileage($mileage);
            $this->vehicles->update($vehicle);
            return $this->vehicles->findById($id);
        });
    }
}

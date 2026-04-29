<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRepositoryInterface;

class DeleteVehicleService
{
    public function __construct(private readonly VehicleRepositoryInterface $vehicleRepository) {}

    public function execute(array $data): bool
    {
        return DB::transaction(function () use ($data): bool {
            $vehicleId = (int) $data['vehicle_id'];
            $tenantId = (int) $data['tenant_id'];

            $vehicle = $this->vehicleRepository->find($vehicleId);
            if ($vehicle === null || $vehicle->getTenantId() !== $tenantId) {
                return false;
            }

            return $this->vehicleRepository->delete($vehicleId);
        });
    }
}

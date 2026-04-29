<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Vehicle\Application\Contracts\CloseVehicleRentalServiceInterface;
use Modules\Vehicle\Domain\Events\VehicleRentalStatusChanged;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRentalRepositoryInterface;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRepositoryInterface;

class CloseVehicleRentalService implements CloseVehicleRentalServiceInterface
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly VehicleRentalRepositoryInterface $rentalRepository,
    ) {}

    public function execute(array $data): bool
    {
        return DB::transaction(function () use ($data): bool {
            $tenantId = (int) $data['tenant_id'];
            $rentalId = (int) $data['rental_id'];

            $rental = $this->rentalRepository->find($tenantId, $rentalId);
            if ($rental === null) {
                return false;
            }

            $updated = $this->rentalRepository->markStatus($tenantId, $rentalId, 'completed', [
                'returned_at' => $data['returned_at'] ?? now(),
                'odometer_in' => $data['odometer_in'] ?? null,
            ]);

            if (! $updated) {
                return false;
            }

            $vehicle = $this->vehicleRepository->find($rental->getVehicleId());
            if ($vehicle !== null) {
                $previousStatus = $vehicle->getRentalStatus();

                $vehicleUpdate = [
                    'rental_status' => 'available',
                ];

                if (array_key_exists('odometer_in', $data) && $data['odometer_in'] !== null) {
                    $vehicleUpdate['odometer'] = $data['odometer_in'];
                }

                $this->vehicleRepository->update((int) $vehicle->getId(), $vehicleUpdate);

                event(new VehicleRentalStatusChanged(
                    tenantId: $tenantId,
                    vehicleId: (int) $vehicle->getId(),
                    previousStatus: $previousStatus,
                    currentStatus: 'available',
                    rentalId: $rentalId,
                ));
            }

            return true;
        });
    }
}

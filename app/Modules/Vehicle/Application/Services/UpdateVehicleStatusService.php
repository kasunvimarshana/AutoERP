<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Vehicle\Application\Contracts\UpdateVehicleStatusServiceInterface;
use Modules\Vehicle\Domain\Events\VehicleRentalStatusChanged;
use Modules\Vehicle\Domain\Events\VehicleServiceStatusChanged;
use Modules\Vehicle\Domain\Exceptions\VehicleStateTransitionException;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRepositoryInterface;

class UpdateVehicleStatusService extends BaseService implements UpdateVehicleStatusServiceInterface
{
    public function __construct(private readonly VehicleRepositoryInterface $vehicleRepository)
    {
        parent::__construct($vehicleRepository);
    }

    protected function handle(array $data): mixed
    {
        $vehicleId = (int) $data['vehicle_id'];
        $tenantId = (int) $data['tenant_id'];

        $vehicle = $this->vehicleRepository->find($vehicleId);

        if ($vehicle === null || $vehicle->getTenantId() !== $tenantId) {
            throw new \RuntimeException('Vehicle not found.');
        }

        $currentRentalStatus = $vehicle->getRentalStatus();
        $currentServiceStatus = $vehicle->getServiceStatus();

        $nextRentalStatus = $data['rental_status'] ?? $currentRentalStatus;
        $nextServiceStatus = $data['service_status'] ?? $currentServiceStatus;

        $vehicle->markRentalStatus((string) $nextRentalStatus);
        $vehicle->markServiceStatus((string) $nextServiceStatus);

        if ((string) $nextRentalStatus === 'rented' && ! $vehicle->canBeRented()) {
            throw VehicleStateTransitionException::cannotRentWhileInService($vehicle->getServiceStatus());
        }

        if ((string) $nextServiceStatus !== 'none' && ! $vehicle->canScheduleService()) {
            throw VehicleStateTransitionException::cannotScheduleServiceWhileRented($vehicle->getRentalStatus());
        }

        $this->vehicleRepository->update($vehicleId, [
            'rental_status' => $nextRentalStatus,
            'service_status' => $nextServiceStatus,
            'next_maintenance_due_at' => $data['next_maintenance_due_at'] ?? $vehicle->getNextMaintenanceDueAt(),
        ]);

        if ($currentRentalStatus !== $nextRentalStatus) {
            $this->addEvent(new VehicleRentalStatusChanged(
                tenantId: $tenantId,
                vehicleId: $vehicleId,
                previousStatus: $currentRentalStatus,
                currentStatus: (string) $nextRentalStatus,
                rentalId: isset($data['rental_id']) ? (int) $data['rental_id'] : null,
            ));
        }

        if ($currentServiceStatus !== $nextServiceStatus) {
            $this->addEvent(new VehicleServiceStatusChanged(
                tenantId: $tenantId,
                vehicleId: $vehicleId,
                previousStatus: $currentServiceStatus,
                currentStatus: (string) $nextServiceStatus,
                jobCardId: isset($data['job_card_id']) ? (int) $data['job_card_id'] : null,
            ));
        }

        return $this->vehicleRepository->find($vehicleId);
    }
}

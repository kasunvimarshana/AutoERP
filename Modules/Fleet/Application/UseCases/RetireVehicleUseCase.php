<?php

namespace Modules\Fleet\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Fleet\Domain\Contracts\VehicleRepositoryInterface;
use Modules\Fleet\Domain\Events\VehicleRetired;

class RetireVehicleUseCase
{
    public function __construct(
        private VehicleRepositoryInterface $vehicleRepo,
    ) {}

    public function execute(string $vehicleId): object
    {
        return DB::transaction(function () use ($vehicleId) {
            $vehicle = $this->vehicleRepo->findById($vehicleId);

            if (! $vehicle) {
                throw new DomainException('Vehicle not found.');
            }

            if ($vehicle->status === 'retired') {
                throw new DomainException('Vehicle is already retired.');
            }

            $updated = $this->vehicleRepo->update($vehicleId, [
                'status' => 'retired',
            ]);

            Event::dispatch(new VehicleRetired($vehicleId, $vehicle->tenant_id));

            return $updated;
        });
    }
}

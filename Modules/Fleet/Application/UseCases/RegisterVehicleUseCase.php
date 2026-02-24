<?php

namespace Modules\Fleet\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Fleet\Domain\Contracts\VehicleRepositoryInterface;
use Modules\Fleet\Domain\Events\VehicleRegistered;

class RegisterVehicleUseCase
{
    public function __construct(
        private VehicleRepositoryInterface $vehicleRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $vehicle = $this->vehicleRepo->create([
                'tenant_id'    => $data['tenant_id'],
                'plate_number' => $data['plate_number'],
                'make'         => $data['make'],
                'model'        => $data['model'],
                'year'         => $data['year'],
                'color'        => $data['color'] ?? null,
                'fuel_type'    => $data['fuel_type'] ?? 'petrol',
                'vin'          => $data['vin'] ?? null,
                'assigned_to'  => $data['assigned_to'] ?? null,
                'status'       => 'active',
                'notes'        => $data['notes'] ?? null,
            ]);

            Event::dispatch(new VehicleRegistered(
                $vehicle->id,
                $vehicle->tenant_id,
                $vehicle->plate_number,
            ));

            return $vehicle;
        });
    }
}

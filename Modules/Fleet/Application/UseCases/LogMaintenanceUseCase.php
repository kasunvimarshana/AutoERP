<?php

namespace Modules\Fleet\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Fleet\Domain\Contracts\MaintenanceRecordRepositoryInterface;
use Modules\Fleet\Domain\Contracts\VehicleRepositoryInterface;
use Modules\Fleet\Domain\Events\MaintenanceLogged;

class LogMaintenanceUseCase
{
    public function __construct(
        private VehicleRepositoryInterface           $vehicleRepo,
        private MaintenanceRecordRepositoryInterface $maintenanceRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $vehicle = $this->vehicleRepo->findById($data['vehicle_id']);

            if (! $vehicle) {
                throw new DomainException('Vehicle not found.');
            }

            if ($vehicle->status === 'retired') {
                throw new DomainException('Cannot log maintenance for a retired vehicle.');
            }

            $record = $this->maintenanceRepo->create([
                'tenant_id'        => $data['tenant_id'],
                'vehicle_id'       => $data['vehicle_id'],
                'maintenance_type' => $data['maintenance_type'],
                'performed_at'     => $data['performed_at'],
                'cost'             => bcadd($data['cost'] ?? '0', '0', 8),
                'odometer_km'      => $data['odometer_km'] ?? null,
                'performed_by'     => $data['performed_by'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);

            Event::dispatch(new MaintenanceLogged(
                $record->id,
                $record->tenant_id,
                $record->vehicle_id,
                $record->maintenance_type,
            ));

            return $record;
        });
    }
}

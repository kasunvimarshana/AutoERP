<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Vehicle\Application\Contracts\CreateVehicleJobCardServiceInterface;
use Modules\Vehicle\Domain\Events\VehicleServiceStatusChanged;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleJobCardRepositoryInterface;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRepositoryInterface;

class CreateVehicleJobCardService implements CreateVehicleJobCardServiceInterface
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly VehicleJobCardRepositoryInterface $jobCardRepository,
    ) {}

    public function execute(array $data): mixed
    {
        return DB::transaction(function () use ($data): mixed {
            $tenantId = (int) $data['tenant_id'];
            $vehicleId = (int) $data['vehicle_id'];

            $vehicle = $this->vehicleRepository->find($vehicleId);
            if ($vehicle === null || $vehicle->getTenantId() !== $tenantId) {
                throw new \RuntimeException('Vehicle not found.');
            }

            if ($this->vehicleRepository->existsActiveRental($tenantId, $vehicleId)) {
                throw new \RuntimeException('Vehicle cannot be scheduled for service while actively reserved/rented.');
            }

            $jobCard = $this->jobCardRepository->create([
                'tenant_id' => $tenantId,
                'vehicle_id' => $vehicleId,
                'customer_id' => $data['customer_id'] ?? null,
                'assigned_mechanic_id' => $data['assigned_mechanic_id'] ?? null,
                'job_card_no' => (string) $data['job_card_no'],
                'workflow_status' => (string) ($data['workflow_status'] ?? 'scheduled'),
                'service_type' => (string) ($data['service_type'] ?? 'maintenance'),
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'tasks' => is_array($data['tasks'] ?? null) ? $data['tasks'] : [],
                'parts' => is_array($data['parts'] ?? null) ? $data['parts'] : [],
                'labor_cost_total' => $data['labor_cost_total'] ?? '0.000000',
                'parts_cost_total' => $data['parts_cost_total'] ?? '0.000000',
                'subtotal' => $data['subtotal'] ?? '0.000000',
                'tax_amount' => $data['tax_amount'] ?? '0.000000',
                'grand_total' => $data['grand_total'] ?? '0.000000',
                'metadata' => $data['metadata'] ?? null,
            ]);

            $previousStatus = $vehicle->getServiceStatus();
            $this->vehicleRepository->update($vehicleId, [
                'service_status' => 'in_maintenance',
            ]);

            event(new VehicleServiceStatusChanged(
                tenantId: $tenantId,
                vehicleId: $vehicleId,
                previousStatus: $previousStatus,
                currentStatus: 'in_maintenance',
                jobCardId: $jobCard->getId(),
            ));

            return $jobCard;
        });
    }
}

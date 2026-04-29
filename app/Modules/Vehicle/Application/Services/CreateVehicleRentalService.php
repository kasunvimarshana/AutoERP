<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Vehicle\Application\Contracts\CreateVehicleRentalServiceInterface;
use Modules\Vehicle\Domain\Events\VehicleRentalStatusChanged;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRentalRepositoryInterface;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRepositoryInterface;

class CreateVehicleRentalService implements CreateVehicleRentalServiceInterface
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly VehicleRentalRepositoryInterface $rentalRepository,
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

            if ($vehicle->getServiceStatus() !== 'none' || $this->vehicleRepository->existsOpenJobCard($tenantId, $vehicleId)) {
                throw new \RuntimeException('Vehicle cannot be rented while in service workflow.');
            }

            $pricing = $this->calculatePricing($data);

            $rental = $this->rentalRepository->create([
                'tenant_id' => $tenantId,
                'vehicle_id' => $vehicleId,
                'customer_id' => $data['customer_id'] ?? null,
                'assigned_driver_id' => $data['assigned_driver_id'] ?? null,
                'rental_no' => (string) $data['rental_no'],
                'rental_status' => (string) ($data['rental_status'] ?? 'reserved'),
                'pricing_model' => (string) $data['pricing_model'],
                'base_rate' => $pricing['base_rate'],
                'distance_km' => $pricing['distance_km'],
                'included_km' => $pricing['included_km'],
                'extra_km_rate' => $pricing['extra_km_rate'],
                'subtotal' => $pricing['subtotal'],
                'tax_amount' => $pricing['tax_amount'],
                'grand_total' => $pricing['grand_total'],
                'reserved_from' => $data['reserved_from'] ?? null,
                'reserved_until' => $data['reserved_until'] ?? null,
                'odometer_out' => $data['odometer_out'] ?? null,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $previousStatus = $vehicle->getRentalStatus();
            $newStatus = $this->mapRentalToVehicleStatus((string) ($data['rental_status'] ?? 'reserved'));

            $this->vehicleRepository->update($vehicleId, [
                'rental_status' => $newStatus,
            ]);

            event(new VehicleRentalStatusChanged(
                tenantId: $tenantId,
                vehicleId: $vehicleId,
                previousStatus: $previousStatus,
                currentStatus: (string) $newStatus,
                rentalId: $rental->getId(),
            ));

            return $rental;
        });
    }

    private function calculatePricing(array $data): array
    {
        $pricingModel = (string) $data['pricing_model'];
        $baseRate = (string) $data['base_rate'];
        $distanceKm = (string) ($data['distance_km'] ?? '0.000000');
        $includedKm = (string) ($data['included_km'] ?? '0.000000');
        $extraKmRate = (string) ($data['extra_km_rate'] ?? '0.000000');
        $taxRate = (string) ($data['tax_rate'] ?? '0.000000');
        $units = (string) ($data['units'] ?? '1.000000');

        $subtotal = bcmul($baseRate, $units, 6);

        if ($pricingModel === 'kilometer') {
            $billableKm = bcsub($distanceKm, $includedKm, 6);
            if (bccomp($billableKm, '0.000000', 6) < 0) {
                $billableKm = '0.000000';
            }

            $subtotal = bcadd($subtotal, bcmul($billableKm, $extraKmRate, 6), 6);
        }

        $taxAmount = bcmul($subtotal, $taxRate, 6);

        return [
            'base_rate' => $baseRate,
            'distance_km' => $distanceKm,
            'included_km' => $includedKm,
            'extra_km_rate' => $extraKmRate,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'grand_total' => bcadd($subtotal, $taxAmount, 6),
        ];
    }

    private function mapRentalToVehicleStatus(string $rentalStatus): string
    {
        return match ($rentalStatus) {
            'active' => 'rented',
            'reserved' => 'reserved',
            'completed', 'cancelled', 'draft' => 'available',
            default => 'reserved',
        };
    }
}

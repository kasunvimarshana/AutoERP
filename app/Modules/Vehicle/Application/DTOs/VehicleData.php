<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\DTOs;

final readonly class VehicleData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public ?string $vehicleCode = null,
        public ?string $vin = null,
        public ?string $licensePlate = null,
        public ?string $make = null,
        public ?string $model = null,
        public ?int $year = null,
        public ?string $color = null,
        public ?string $category = null,
        public string $usageProfile = 'dual',
        public ?string $fuelType = null,
        public ?string $transmission = null,
        public ?int $seatingCapacity = null,
        public int $currentOdometer = 0,
        public string $status = 'active',
        public ?string $registrationExpiry = null,
        public ?string $insuranceExpiry = null,
        public ?string $lastServiceDate = null,
        public ?int $lastServiceOdometer = null,
        public ?string $nextServiceDueDate = null,
        public ?int $nextServiceDueOdometer = null,
        public ?array $metadata = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int|string $tenantId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            vehicleCode: $data['vehicle_code'] ?? null,
            vin: $data['vin'] ?? null,
            licensePlate: $data['license_plate'] ?? null,
            make: $data['make'] ?? null,
            model: $data['model'] ?? null,
            year: isset($data['year']) ? (int) $data['year'] : null,
            color: $data['color'] ?? null,
            category: $data['category'] ?? null,
            usageProfile: (string) ($data['usage_profile'] ?? 'dual'),
            fuelType: $data['fuel_type'] ?? null,
            transmission: $data['transmission'] ?? null,
            seatingCapacity: isset($data['seating_capacity']) ? (int) $data['seating_capacity'] : null,
            currentOdometer: isset($data['current_odometer']) ? (int) $data['current_odometer'] : 0,
            status: (string) ($data['status'] ?? 'active'),
            registrationExpiry: $data['registration_expiry'] ?? null,
            insuranceExpiry: $data['insurance_expiry'] ?? null,
            lastServiceDate: $data['last_service_date'] ?? null,
            lastServiceOdometer: isset($data['last_service_odometer']) ? (int) $data['last_service_odometer'] : null,
            nextServiceDueDate: $data['next_service_due_date'] ?? null,
            nextServiceDueOdometer: isset($data['next_service_due_odometer'])
                ? (int) $data['next_service_due_odometer']
                : null,
            metadata: $data['metadata'] ?? null,
        );
    }
}

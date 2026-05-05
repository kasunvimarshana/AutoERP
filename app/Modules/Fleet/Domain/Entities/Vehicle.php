<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\Entities;

use Modules\Fleet\Domain\ValueObjects\VehicleState;

class Vehicle
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $vehicleTypeId,
        public readonly string $registrationNumber,
        public readonly string $make,
        public readonly string $model,
        public readonly int $year,
        public readonly string $ownershipType,
        public readonly bool $isRentable,
        public readonly bool $isServiceable,
        public readonly string $currentState,
        public readonly string $currentOdometer,
        public readonly string $fuelType,
        public readonly string $transmission,
        public readonly int $seatingCapacity,
        public readonly ?string $color = null,
        public readonly ?string $vinNumber = null,
        public readonly ?string $engineNumber = null,
        public readonly ?int $ownerSupplierId = null,
        public readonly string $ownerCommissionPct = '0.00',
        public readonly ?string $fuelCapacity = null,
        public readonly ?int $assetAccountId = null,
        public readonly ?int $accumDepreciationAccountId = null,
        public readonly ?int $depreciationExpenseAccountId = null,
        public readonly ?int $rentalRevenueAccountId = null,
        public readonly ?int $serviceRevenueAccountId = null,
        public readonly ?string $acquisitionCost = null,
        public readonly ?string $acquiredAt = null,
        public readonly ?string $disposedAt = null,
        public readonly ?array $metadata = null,
        public readonly bool $isActive = true,
        public readonly ?int $orgUnitId = null,
        public readonly ?int $id = null,
    ) {}

    public function isAvailable(): bool
    {
        return $this->currentState === VehicleState::AVAILABLE;
    }

    public function canBeRented(): bool
    {
        return $this->isRentable && $this->isAvailable();
    }

    public function canBeServiced(): bool
    {
        return $this->isServiceable && $this->isAvailable();
    }

    public function canTransitionTo(string $newState): bool
    {
        return VehicleState::canTransition($this->currentState, $newState);
    }
}

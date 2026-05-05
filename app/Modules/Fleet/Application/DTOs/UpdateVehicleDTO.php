<?php

declare(strict_types=1);

namespace Modules\Fleet\Application\DTOs;

final class UpdateVehicleDTO
{
    public function __construct(
        public readonly int $vehicleId,
        public readonly ?int $vehicleTypeId = null,
        public readonly ?string $color = null,
        public readonly ?bool $isRentable = null,
        public readonly ?bool $isServiceable = null,
        public readonly ?int $ownerSupplierId = null,
        public readonly ?string $ownerCommissionPct = null,
        public readonly ?int $assetAccountId = null,
        public readonly ?int $accumDepreciationAccountId = null,
        public readonly ?int $depreciationExpenseAccountId = null,
        public readonly ?int $rentalRevenueAccountId = null,
        public readonly ?int $serviceRevenueAccountId = null,
        public readonly ?string $acquisitionCost = null,
        public readonly ?array $metadata = null,
        public readonly ?bool $isActive = null,
    ) {}
}

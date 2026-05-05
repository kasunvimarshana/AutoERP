<?php

declare(strict_types=1);

namespace Modules\Rental\Application\DTOs;

class CreateAssetDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $assetCode,
        public readonly string $assetName,
        public readonly string $usageMode,
        public readonly ?int $orgUnitId = null,
        public readonly ?int $productId = null,
        public readonly ?int $serialId = null,
        public readonly ?int $supplierId = null,
        public readonly ?int $warehouseId = null,
        public readonly ?int $currencyId = null,
        public readonly ?int $createdBy = null,
        public readonly ?string $registrationNumber = null,
        public readonly ?string $chassisNumber = null,
        public readonly ?string $engineNumber = null,
        public readonly ?int $yearOfManufacture = null,
        public readonly ?string $make = null,
        public readonly ?string $model = null,
        public readonly ?string $color = null,
        public readonly ?string $fuelType = null,
        public readonly ?string $purchaseCost = null,
        public readonly ?string $bookValue = null,
        public readonly ?string $purchaseDate = null,
        public readonly ?string $currentOdometer = null,
        public readonly ?string $engineHours = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
    ) {}
}

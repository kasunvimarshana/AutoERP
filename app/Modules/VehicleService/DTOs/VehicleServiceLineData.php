<?php

declare(strict_types=1);

namespace Modules\VehicleService\DTOs;

use Modules\VehicleService\Enums\VehicleServiceLineSourceType;

final readonly class VehicleServiceLineData
{
    public function __construct(
        public VehicleServiceLineSourceType $lineSourceType,
        public string $description,
        public string $quantity,
        public string $unitPrice,
        public ?int $parentLineId = null,
        public ?int $itemId = null,
        public ?int $itemVariantId = null,
        public ?int $uomId = null,
        public string $unitCost = '0.000000',
        public ?string $discountCalculationType = null,
        public string $discountRate = '0.000000',
        public string $discountAmount = '0.000000',
        public ?string $taxCalculationType = null,
        public string $taxRate = '0.000000',
        public string $taxAmount = '0.000000',
        public ?string $chargeCalculationType = null,
        public string $chargeRate = '0.000000',
        public string $chargeAmount = '0.000000',
        public ?bool $isInventoryTracked = null,
        public bool $isCustomerSupplied = false,
        public ?bool $isExternal = null,
        public ?bool $isBillable = null,
        public ?bool $isEmployeeAssignable = null,
        public bool $expandCombo = true,
    ) {}
}

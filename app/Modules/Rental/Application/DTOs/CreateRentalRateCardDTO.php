<?php

declare(strict_types=1);

namespace Modules\Rental\Application\DTOs;

class CreateRentalRateCardDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $billingUom,
        public readonly string $rate,
        public readonly ?int $orgUnitId = null,
        public readonly ?int $assetId = null,
        public readonly ?int $productId = null,
        public readonly ?int $customerId = null,
        public readonly ?string $depositPercentage = null,
        public readonly int $priority = 100,
        public readonly ?string $validFrom = null,
        public readonly ?string $validTo = null,
        public readonly ?string $notes = null,
    ) {}
}

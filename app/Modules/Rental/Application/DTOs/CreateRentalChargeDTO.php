<?php

declare(strict_types=1);

namespace Modules\Rental\Application\DTOs;

use Modules\Rental\Domain\ValueObjects\ChargeType;

class CreateRentalChargeDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $rentalId,
        public readonly ChargeType $chargeType,
        public readonly string $description,
        public readonly string $quantity,
        public readonly string $unitPrice,
    ) {}
}

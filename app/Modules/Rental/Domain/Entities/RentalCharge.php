<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

use Modules\Rental\Domain\ValueObjects\ChargeType;

class RentalCharge
{
    public function __construct(
        public readonly int|null $id,
        public readonly int $tenantId,
        public readonly int $rentalId,
        public readonly ChargeType $chargeType,
        public readonly string $description,
        public readonly string $quantity,
        public readonly string $unitPrice,
        public readonly string $amount,
        public readonly bool $isActive,
    ) {}
}

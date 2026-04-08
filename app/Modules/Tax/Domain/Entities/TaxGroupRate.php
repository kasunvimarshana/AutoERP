<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\Entities;

use DateTimeInterface;

class TaxGroupRate
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $taxGroupId,
        public readonly string $name,
        public readonly float $rate,
        public readonly string $type,
        public readonly int $sequence,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}

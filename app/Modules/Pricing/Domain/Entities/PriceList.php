<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Entities;

use DateTimeInterface;

class PriceList
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $currency,
        public readonly bool $isDefault,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}

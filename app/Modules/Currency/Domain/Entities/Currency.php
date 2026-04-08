<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Entities;

use DateTimeInterface;

class Currency
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $symbol,
        public readonly int $decimalPlaces,
        public readonly bool $isBase,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isBaseCurrency(): bool
    {
        return $this->isBase;
    }

    public function format(float $amount): string
    {
        return $this->symbol . number_format($amount, $this->decimalPlaces);
    }
}

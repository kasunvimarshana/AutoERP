<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Entities;

use DateTimeInterface;

class ExchangeRate
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $fromCurrency,
        public readonly string $toCurrency,
        public readonly float $rate,
        public readonly DateTimeInterface $effectiveDate,
        public readonly string $source,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function convert(float $amount): float
    {
        return $amount * $this->rate;
    }

    public function getInverseRate(): float
    {
        if (abs($this->rate) < PHP_FLOAT_EPSILON) {
            return 0.0;
        }

        return 1.0 / $this->rate;
    }
}

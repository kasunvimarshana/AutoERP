<?php

namespace Modules\Currency\Domain\Entities;

class ExchangeRate
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenantId,
        public readonly string  $fromCurrencyCode,
        public readonly string  $toCurrencyCode,
        public readonly string  $rate,
        public readonly string  $source,
        public readonly ?string $effectiveDate,
    ) {}
}

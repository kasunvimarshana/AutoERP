<?php

namespace Modules\Currency\Domain\Events;

class ExchangeRateRecorded
{
    public function __construct(
        public readonly string $exchangeRateId,
        public readonly string $tenantId,
        public readonly string $fromCurrencyCode,
        public readonly string $toCurrencyCode,
        public readonly string $rate,
    ) {}
}

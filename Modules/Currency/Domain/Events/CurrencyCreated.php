<?php

namespace Modules\Currency\Domain\Events;

class CurrencyCreated
{
    public function __construct(
        public readonly string $currencyId,
        public readonly string $tenantId,
        public readonly string $code,
        public readonly string $name,
    ) {}
}

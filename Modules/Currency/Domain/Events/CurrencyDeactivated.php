<?php

namespace Modules\Currency\Domain\Events;

class CurrencyDeactivated
{
    public function __construct(
        public readonly string $currencyId,
        public readonly string $tenantId,
        public readonly string $code,
    ) {}
}

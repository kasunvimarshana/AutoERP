<?php

namespace Modules\Tax\Domain\Events;

class TaxRateCreated
{
    public function __construct(
        public readonly string $taxRateId,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $rate,
    ) {}
}

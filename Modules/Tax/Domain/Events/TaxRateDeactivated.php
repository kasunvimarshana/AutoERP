<?php

namespace Modules\Tax\Domain\Events;

class TaxRateDeactivated
{
    public function __construct(
        public readonly string $taxRateId,
        public readonly string $tenantId,
    ) {}
}

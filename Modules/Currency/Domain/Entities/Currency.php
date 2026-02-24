<?php

namespace Modules\Currency\Domain\Entities;

class Currency
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $symbol,
        public readonly int    $decimalPlaces,
        public readonly bool   $isActive,
    ) {}
}

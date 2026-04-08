<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class UnitOfMeasure
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly bool $isBaseUnit,
        public readonly float $conversionFactor,
    ) {}
}

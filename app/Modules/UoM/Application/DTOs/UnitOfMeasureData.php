<?php
namespace Modules\UoM\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;

class UnitOfMeasureData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $categoryId,
        public readonly string $name,
        public readonly string $symbol,
        public readonly float $conversionFactor = 1.0,
        public readonly bool $isBase = false,
        public readonly bool $isActive = true,
    ) {}
}

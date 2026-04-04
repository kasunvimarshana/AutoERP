<?php
namespace Modules\UoM\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;

class UnitOfMeasure extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $categoryId,
        public readonly string $name,
        public readonly string $symbol,
        public readonly float $conversionFactor = 1.0,
        public readonly bool $isBase = false,
        public readonly bool $isActive = true,
    ) {
        parent::__construct($id);
    }
}

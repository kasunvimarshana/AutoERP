<?php
namespace Modules\Pricing\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;

class TaxRate extends BaseEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly float $rate,
        public readonly string $type = 'percentage',
        public readonly bool $isActive = true,
        public readonly ?string $description = null,
        public readonly ?string $region = null,
        public readonly ?string $taxClass = null,
    ) { parent::__construct($id); }
}

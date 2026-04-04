<?php
namespace Modules\Pricing\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;

class PriceList extends BaseEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $currency = 'USD',
        public readonly bool $isDefault = false,
        public readonly bool $isActive = true,
        public readonly ?string $validFrom = null,
        public readonly ?string $validTo = null,
        public readonly ?string $description = null,
    ) { parent::__construct($id); }
}

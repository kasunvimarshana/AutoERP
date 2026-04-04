<?php
namespace Modules\Pricing\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;

class TaxRateData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly float $rate,
        public readonly string $type = 'percentage',
        public readonly bool $isActive = true,
        public readonly ?string $description = null,
        public readonly ?string $region = null,
        public readonly ?string $taxClass = null,
    ) {}
}

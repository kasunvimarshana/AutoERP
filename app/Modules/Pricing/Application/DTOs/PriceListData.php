<?php
namespace Modules\Pricing\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;

class PriceListData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $currency = 'USD',
        public readonly bool $isDefault = false,
        public readonly bool $isActive = true,
        public readonly ?string $validFrom = null,
        public readonly ?string $validTo = null,
        public readonly ?string $description = null,
    ) {}
}

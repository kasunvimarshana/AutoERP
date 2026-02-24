<?php
namespace Modules\Sales\Domain\Entities;

class PriceList
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenantId,
        public readonly string  $name,
        public readonly string  $currencyCode,
        public readonly bool    $isActive,
        public readonly ?string $validFrom,
        public readonly ?string $validTo,
        public readonly ?string $customerGroup,
    ) {}
}

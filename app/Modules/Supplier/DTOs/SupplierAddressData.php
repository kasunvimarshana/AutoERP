<?php

declare(strict_types=1);

namespace Modules\Supplier\DTOs;

use Modules\Supplier\Enums\SupplierAddressType;

final readonly class SupplierAddressData
{
    public function __construct(
        public SupplierAddressType $addressType,
        public string $addressLine1,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $postalCode = null,
        public ?string $country = null,
        public bool $isPrimary = false,
        public bool $isActive = true,
    ) {}
}

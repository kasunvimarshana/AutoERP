<?php

declare(strict_types=1);

namespace Modules\Customer\DTOs;

use Modules\Customer\Enums\CustomerAddressType;

final readonly class CustomerAddressData
{
    public function __construct(
        public CustomerAddressType $addressType,
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

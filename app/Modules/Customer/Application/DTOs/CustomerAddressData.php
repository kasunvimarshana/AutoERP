<?php
namespace Modules\Customer\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class CustomerAddressData extends BaseDTO
{
    public function __construct(
        public readonly int $customerId,
        public readonly string $addressType = 'billing',
        public readonly string $street = '',
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $country = null,
        public readonly ?string $postalCode = null,
        public readonly bool $isDefault = false,
    ) {}
}

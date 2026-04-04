<?php
namespace Modules\Customer\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class CustomerAddress extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $customerId,
        public readonly string $addressType = 'billing',
        public readonly string $street = '',
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $country = null,
        public readonly ?string $postalCode = null,
        public readonly bool $isDefault = false,
    ) {
        parent::__construct($id);
    }
}

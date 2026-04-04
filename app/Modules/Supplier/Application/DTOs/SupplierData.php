<?php
namespace Modules\Supplier\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class SupplierData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $status = 'active',
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $country = null,
        public readonly ?string $taxNumber = null,
        public readonly ?string $currency = 'USD',
        public readonly ?string $notes = null,
    ) {}
}

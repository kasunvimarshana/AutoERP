<?php
namespace Modules\Customer\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class CustomerData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $status = 'active',
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $taxNumber = null,
        public readonly ?string $currency = 'USD',
        public readonly ?float $creditLimit = null,
        public readonly ?string $notes = null,
    ) {}
}

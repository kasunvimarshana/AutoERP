<?php
namespace Modules\Customer\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class Customer extends BaseEntity
{
    public function __construct(
        ?int $id,
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
    ) {
        parent::__construct($id);
    }
}

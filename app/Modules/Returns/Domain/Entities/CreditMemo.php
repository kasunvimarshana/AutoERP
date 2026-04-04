<?php

namespace Modules\Returns\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class CreditMemo extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $memoNumber,
        public readonly int $stockReturnId,
        public readonly float $amount,
        public readonly string $status,
        public readonly ?int $customerId = null,
        public readonly ?string $currency = 'USD',
        public readonly ?string $notes = null,
        public readonly ?\DateTimeImmutable $issuedAt = null,
        public readonly ?int $issuedBy = null,
    ) {
        parent::__construct($id);
    }
}

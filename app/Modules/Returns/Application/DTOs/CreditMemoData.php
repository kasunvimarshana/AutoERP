<?php

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class CreditMemoData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $stockReturnId,
        public readonly string $memoNumber,
        public readonly float $amount,
        public readonly ?int $customerId = null,
        public readonly ?string $currency = 'USD',
        public readonly ?string $notes = null,
    ) {}
}

<?php
namespace Modules\Accounting\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class PaymentData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $referenceNumber,
        public readonly string $method,
        public readonly float $amount,
        public readonly string $currency = 'USD',
        public readonly ?string $payableType = null,
        public readonly ?int $payableId = null,
        public readonly ?int $paidBy = null,
        public readonly ?string $notes = null,
    ) {}
}

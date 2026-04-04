<?php
namespace Modules\Accounting\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class RefundData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $paymentId,
        public readonly float $amount,
        public readonly string $currency = 'USD',
        public readonly ?string $reason = null,
        public readonly ?int $processedBy = null,
    ) {}
}

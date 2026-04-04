<?php
namespace Modules\Accounting\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class Refund extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $paymentId,
        public readonly float $amount,
        public readonly string $currency = 'USD',
        public readonly string $status = 'pending',
        public readonly ?string $reason = null,
        public readonly ?int $processedBy = null,
        public readonly ?string $processedAt = null,
        public readonly ?int $journalEntryId = null,
    ) {
        parent::__construct($id);
    }
}

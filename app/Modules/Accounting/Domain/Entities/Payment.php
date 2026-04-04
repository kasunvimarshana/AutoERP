<?php
namespace Modules\Accounting\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class Payment extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $referenceNumber,
        public readonly string $status,
        public readonly string $method,
        public readonly float $amount,
        public readonly string $currency = 'USD',
        public readonly ?string $payableType = null,
        public readonly ?int $payableId = null,
        public readonly ?int $paidBy = null,
        public readonly ?string $paidAt = null,
        public readonly ?string $notes = null,
        public readonly ?int $journalEntryId = null,
    ) {
        parent::__construct($id);
    }
}

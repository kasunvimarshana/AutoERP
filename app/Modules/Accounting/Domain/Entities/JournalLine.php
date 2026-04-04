<?php
namespace Modules\Accounting\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class JournalLine extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $journalEntryId,
        public readonly int $accountId,
        public readonly float $debit,
        public readonly float $credit,
        public readonly string $currency = 'USD',
        public readonly ?string $description = null,
    ) {
        if ($debit < 0 || $credit < 0) {
            throw new \DomainException('JournalLine debit and credit must be non-negative');
        }
        if ($debit > 0 && $credit > 0) {
            throw new \DomainException('JournalLine cannot have both debit and credit');
        }
        parent::__construct($id);
    }
}

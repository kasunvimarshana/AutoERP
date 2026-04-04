<?php
namespace Modules\Accounting\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class JournalLineData extends BaseDTO
{
    public function __construct(
        public readonly int $journalEntryId,
        public readonly int $accountId,
        public readonly float $debit,
        public readonly float $credit,
        public readonly string $currency = 'USD',
        public readonly ?string $description = null,
    ) {}
}

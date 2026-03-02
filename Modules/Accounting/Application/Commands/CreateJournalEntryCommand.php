<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Commands;

final readonly class CreateJournalEntryCommand
{
    /**
     * @param  array<int, array{account_id: int, description?: ?string, debit_amount: string, credit_amount: string}>  $lines
     */
    public function __construct(
        public int $tenantId,
        public string $entryDate,
        public ?string $reference,
        public ?string $description,
        public string $currency,
        public array $lines,
    ) {}
}

<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Events;

class BankTransactionImported
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $bankAccountId,
        public readonly int $importedCount,
    ) {}
}

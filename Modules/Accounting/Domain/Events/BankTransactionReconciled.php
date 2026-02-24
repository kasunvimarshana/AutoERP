<?php

namespace Modules\Accounting\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class BankTransactionReconciled extends DomainEvent
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $tenantId,
        public readonly string $journalEntryId,
    ) {
        parent::__construct();
    }
}

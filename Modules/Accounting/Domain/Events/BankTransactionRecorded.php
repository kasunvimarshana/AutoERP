<?php

namespace Modules\Accounting\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class BankTransactionRecorded extends DomainEvent
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $tenantId,
        public readonly string $bankAccountId,
        public readonly string $type,
        public readonly string $amount,
    ) {
        parent::__construct();
    }
}

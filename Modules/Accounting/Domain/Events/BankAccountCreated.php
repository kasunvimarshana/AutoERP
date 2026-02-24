<?php

namespace Modules\Accounting\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class BankAccountCreated extends DomainEvent
{
    public function __construct(
        public readonly string $bankAccountId,
        public readonly string $tenantId,
        public readonly string $name,
    ) {
        parent::__construct();
    }
}

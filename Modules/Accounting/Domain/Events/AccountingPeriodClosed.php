<?php

namespace Modules\Accounting\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class AccountingPeriodClosed extends DomainEvent
{
    public function __construct(
        public readonly string  $periodId,
        public readonly string  $tenantId,
        public readonly ?string $closedBy,
    ) {
        parent::__construct();
    }
}

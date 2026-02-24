<?php

namespace Modules\Accounting\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class AccountingPeriodCreated extends DomainEvent
{
    public function __construct(
        public readonly string $periodId,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $startDate,
        public readonly string $endDate,
    ) {
        parent::__construct();
    }
}

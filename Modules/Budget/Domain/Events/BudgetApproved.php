<?php

namespace Modules\Budget\Domain\Events;

class BudgetApproved
{
    public function __construct(
        public readonly string $budgetId,
        public readonly string $tenantId,
        public readonly string $approverId,
    ) {}
}

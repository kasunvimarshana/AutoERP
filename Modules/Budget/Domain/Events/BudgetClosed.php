<?php

namespace Modules\Budget\Domain\Events;

class BudgetClosed
{
    public function __construct(
        public readonly string $budgetId,
        public readonly string $tenantId,
    ) {}
}

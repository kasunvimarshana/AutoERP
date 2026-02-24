<?php

namespace Modules\Budget\Domain\Events;

class BudgetLineOverspent
{
    public function __construct(
        public readonly string $budgetLineId,
        public readonly string $budgetId,
        public readonly string $tenantId,
        public readonly string $category,
        public readonly string $plannedAmount,
        public readonly string $actualAmount,
        public readonly string $overspendAmount,
    ) {}
}

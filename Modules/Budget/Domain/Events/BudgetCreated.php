<?php

namespace Modules\Budget\Domain\Events;

class BudgetCreated
{
    public function __construct(
        public readonly string $budgetId,
        public readonly string $tenantId,
        public readonly string $name,
    ) {}
}

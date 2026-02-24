<?php

namespace Modules\Expense\Domain\Events;

class ExpenseClaimSubmitted
{
    public function __construct(
        public readonly string $claimId,
        public readonly string $tenantId,
    ) {}
}

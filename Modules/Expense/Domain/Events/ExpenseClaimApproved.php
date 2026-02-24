<?php

namespace Modules\Expense\Domain\Events;

class ExpenseClaimApproved
{
    public function __construct(
        public readonly string $claimId,
        public readonly string $tenantId,
        public readonly string $approverId,
    ) {}
}

<?php

namespace Modules\Expense\Domain\Events;

class ExpenseClaimReimbursed
{
    public function __construct(
        public readonly string $claimId,
        public readonly string $tenantId,
        public readonly string $employeeId = '',
        public readonly string $totalAmount = '0',
        public readonly string $currency = 'USD',
    ) {}
}

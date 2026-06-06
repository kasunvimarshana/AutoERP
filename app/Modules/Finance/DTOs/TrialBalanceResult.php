<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

final readonly class TrialBalanceResult
{
    /**
     * @param  list<AccountBalanceResult>  $accountBalances
     */
    public function __construct(
        public string $totalDebit,
        public string $totalCredit,
        public bool $isBalanced,
        public array $accountBalances = [],
    ) {}
}

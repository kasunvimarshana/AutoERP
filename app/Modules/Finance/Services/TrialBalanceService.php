<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\TrialBalanceResult;
use Modules\Finance\Models\FinanceAccountBalance;

final class TrialBalanceService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly AccountBalanceService $balances,
    ) {}

    public function calculate(int $tenantId, ?int $organizationUnitId = null, ?int $fiscalPeriodId = null): TrialBalanceResult
    {
        $query = FinanceAccountBalance::query()
            ->with('account')
            ->where('tenant_id', $tenantId);

        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        $fiscalPeriodId === null
            ? $query->whereNull('fiscal_period_id')
            : $query->where('fiscal_period_id', $fiscalPeriodId);

        $totalDebit = '0.000000';
        $totalCredit = '0.000000';
        $accountBalances = [];

        foreach ($query->get() as $balance) {
            $totalDebit = $this->math->add($totalDebit, (string) $balance->closing_debit);
            $totalCredit = $this->math->add($totalCredit, (string) $balance->closing_credit);
            $accountBalances[] = $this->balances->result($balance);
        }

        return new TrialBalanceResult(
            totalDebit: $totalDebit,
            totalCredit: $totalCredit,
            isBalanced: $this->math->compare($totalDebit, $totalCredit) === 0,
            accountBalances: $accountBalances,
        );
    }
}

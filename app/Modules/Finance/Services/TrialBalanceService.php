<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\TrialBalanceResult;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceFiscalPeriod;

final class TrialBalanceService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly AccountBalanceService $balances,
    ) {}

    public function calculate(
        int $tenantId,
        ?int $organizationUnitId = null,
        ?int $fiscalPeriodId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): TrialBalanceResult {
        if ($fiscalPeriodId !== null) {
            $period = FinanceFiscalPeriod::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($fiscalPeriodId);
            $dateFrom = $period->start_date->toDateString();
            $dateTo = $period->end_date->toDateString();
        }

        $query = FinanceAccount::query()
            ->where('tenant_id', $tenantId);

        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        $totalDebit = '0.000000';
        $totalCredit = '0.000000';
        $accountBalances = $this->balances->forAccounts(
            $query->orderBy('code')->get(),
            $dateFrom,
            $dateTo,
        );

        foreach ($accountBalances as $balance) {
            $totalDebit = $this->math->add($totalDebit, $balance->closingDebit);
            $totalCredit = $this->math->add($totalCredit, $balance->closingCredit);
        }

        return new TrialBalanceResult(
            totalDebit: $totalDebit,
            totalCredit: $totalCredit,
            isBalanced: $this->math->compare($totalDebit, $totalCredit) === 0,
            accountBalances: $accountBalances,
        );
    }
}

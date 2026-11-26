<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Finance\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\TrialBalanceResult;
use Modules\Finance\Models\FinanceAccount;

final class TrialBalanceService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly AccountBalanceService $balances,
    ) {}

    public function calculate(
        int $tenantId,
        ?int $organizationUnitId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): TrialBalanceResult {
        $query = FinanceAccount::query()
            ->where('tenant_id', $tenantId);

        $this->scopeOrganization($query, $organizationUnitId);

        $totalDebit = '0.000000';
        $totalCredit = '0.000000';
        $accountBalances = $this->balances->forAccounts(
            $query->orderBy('code')->get(),
            $dateFrom,
            $dateTo,
            $organizationUnitId,
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

    private function scopeOrganization(Builder $query, ?int $organizationUnitId): Builder
    {
        if ($organizationUnitId !== null) {
            return $query->where(function (Builder $scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId);
            });
        }

        return $query;
    }
}

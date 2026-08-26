<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Finance\Models\FinanceAccount;

final class FinanceStatementService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly AccountBalanceService $balances,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function profitAndLoss(
        int $tenantId,
        ?int $organizationUnitId,
        ?string $dateFrom,
        ?string $dateTo,
    ): array {
        $accounts = $this->accounts($tenantId, $organizationUnitId, StatementType::IncomeStatement);
        $balances = $this->balances->forAccounts($accounts, $dateFrom, $dateTo);
        $rows = [];
        $revenue = '0.000000';
        $expenses = '0.000000';

        foreach ($accounts as $index => $account) {
            $balance = $balances[$index];
            $amount = $this->periodAmount($balance->normalBalance, $balance->periodDebit, $balance->periodCredit);
            $typeCode = (string) $account->accountType->code;

            if ($balance->normalBalance === NormalBalance::Credit) {
                $revenue = $this->math->add($revenue, $amount);
            } else {
                $expenses = $this->math->add($expenses, $amount);
            }

            $rows[] = [
                'account_id' => $balance->accountId,
                'account_code' => $balance->accountCode,
                'account_name' => $balance->accountName,
                'account_type' => $typeCode,
                'account_category_code' => $account->accountCategory?->code,
                'account_category_name' => $account->accountCategory?->name,
                'amount' => $amount,
            ];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'rows' => $rows,
            'total_revenue' => $revenue,
            'total_expenses' => $expenses,
            'net_profit' => $this->math->sub($revenue, $expenses),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function balanceSheet(
        int $tenantId,
        ?int $organizationUnitId,
        ?string $dateTo,
    ): array {
        $accounts = $this->accounts($tenantId, $organizationUnitId, StatementType::BalanceSheet);
        $balances = $this->balances->forAccounts($accounts, null, $dateTo);
        $rows = [];
        $assets = '0.000000';
        $liabilities = '0.000000';
        $equity = '0.000000';

        foreach ($accounts as $index => $account) {
            $balance = $balances[$index];
            $typeCode = strtoupper((string) $account->accountType->code);
            $amount = $balance->balance;

            if ($typeCode === 'ASSET') {
                $assets = $this->math->add($assets, $amount);
            } elseif ($typeCode === 'LIABILITY') {
                $liabilities = $this->math->add($liabilities, $amount);
            } elseif ($typeCode === 'EQUITY') {
                $equity = $this->math->add($equity, $amount);
            }

            $rows[] = [
                'account_id' => $balance->accountId,
                'account_code' => $balance->accountCode,
                'account_name' => $balance->accountName,
                'account_type' => $typeCode,
                'amount' => $amount,
            ];
        }

        $incomeStatement = $this->profitAndLoss($tenantId, $organizationUnitId, null, $dateTo);
        $currentEarnings = (string) $incomeStatement['net_profit'];
        $liabilitiesAndEquity = $this->math->add(
            $liabilities,
            $this->math->add($equity, $currentEarnings),
        );

        return [
            'date_to' => $dateTo,
            'rows' => $rows,
            'total_assets' => $assets,
            'total_liabilities' => $liabilities,
            'total_equity' => $equity,
            'current_earnings' => $currentEarnings,
            'liabilities_and_equity' => $liabilitiesAndEquity,
            'difference' => $this->math->sub($assets, $liabilitiesAndEquity),
        ];
    }

    private function accounts(
        int $tenantId,
        ?int $organizationUnitId,
        StatementType $statementType,
    ) {
        $query = FinanceAccount::query()
            ->with(['accountType', 'accountCategory'])
            ->where('tenant_id', $tenantId)
            ->whereHas('accountType', fn ($scope) => $scope->where('statement_type', $statementType->value));

        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        return $query->orderBy('code')->get();
    }

    private function periodAmount(NormalBalance $normalBalance, string $debit, string $credit): string
    {
        return $normalBalance === NormalBalance::Debit
            ? $this->math->sub($debit, $credit)
            : $this->math->sub($credit, $debit);
    }
}

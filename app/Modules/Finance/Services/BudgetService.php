<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceBudget;
use Modules\Finance\Models\FinanceBudgetLine;
use Modules\Finance\Models\FinanceLedgerEntry;

final class BudgetService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function save(
        int $tenantId,
        ?int $organizationUnitId,
        int $budgetYear,
        string $name,
        array $lines,
        string $status = 'draft',
        ?string $description = null,
        ?FinanceBudget $budget = null,
    ): FinanceBudget {
        return DB::transaction(function () use (
            $tenantId,
            $organizationUnitId,
            $budgetYear,
            $name,
            $lines,
            $status,
            $description,
            $budget,
        ): FinanceBudget {
            if ($budgetYear < 1900 || $budgetYear > 2200) {
                throw new InvalidArgumentException('Budget year is invalid.');
            }
            if (trim($name) === '') {
                throw new InvalidArgumentException('Budget name is required.');
            }
            if ($lines === []) {
                throw new InvalidArgumentException('Budget requires at least one line.');
            }

            $budget ??= new FinanceBudget;
            $budget->forceFill([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'budget_year' => $budgetYear,
                'name' => trim($name),
                'status' => $status,
                'description' => $description,
            ])->save();

            $budget->lines()->delete();
            foreach ($lines as $line) {
                $account = FinanceAccount::query()->findOrFail((int) $line['account_id']);
                if ((int) $account->tenant_id !== $tenantId || $account->organization_unit_id !== $organizationUnitId) {
                    throw new InvalidArgumentException('Budget account scope mismatch.');
                }
                $amount = $this->math->normalize((string) ($line['amount'] ?? '0.000000'));
                if ($this->math->isNegative($amount)) {
                    throw new InvalidArgumentException('Budget line amount cannot be negative.');
                }

                FinanceBudgetLine::query()->create([
                    'budget_id' => $budget->getKey(),
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'account_id' => $account->getKey(),
                    'dimension_id' => $line['dimension_id'] ?? null,
                    'budget_month' => $line['budget_month'] ?? null,
                    'amount' => $amount,
                ]);
            }

            return $budget->refresh()->load(['lines.account', 'lines.dimension']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function actualVsBudget(FinanceBudget $budget): array
    {
        $budget->loadMissing(['lines.account']);
        $rows = [];
        $totalBudget = '0.000000';
        $totalActual = '0.000000';

        foreach ($budget->lines as $line) {
            $account = $line->account;
            if (! $account instanceof FinanceAccount) {
                continue;
            }

            $actual = $this->actualForLine($line, $account);
            $budgetAmount = $this->math->normalize((string) $line->amount);
            $rows[] = [
                'account_id' => (int) $account->getKey(),
                'account_code' => (string) $account->code,
                'account_name' => (string) $account->name,
                'budget_month' => $line->budget_month,
                'budget_amount' => $budgetAmount,
                'actual_amount' => $actual,
                'variance' => $this->math->sub($actual, $budgetAmount),
            ];
            $totalBudget = $this->math->add($totalBudget, $budgetAmount);
            $totalActual = $this->math->add($totalActual, $actual);
        }

        return [
            'budget_id' => (int) $budget->getKey(),
            'name' => (string) $budget->name,
            'budget_year' => (int) $budget->budget_year,
            'status' => (string) $budget->status,
            'total_budget' => $totalBudget,
            'total_actual' => $totalActual,
            'variance' => $this->math->sub($totalActual, $totalBudget),
            'rows' => $rows,
        ];
    }

    private function actualForLine(FinanceBudgetLine $line, FinanceAccount $account): string
    {
        $query = FinanceLedgerEntry::query()
            ->where('tenant_id', $line->tenant_id)
            ->where('account_id', $line->account_id);

        $line->organization_unit_id === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $line->organization_unit_id);

        if ($line->budget_month !== null) {
            $query->whereMonth('entry_date', (int) $line->budget_month);
            $query->whereYear('entry_date', (int) $line->budget->budget_year);
        } else {
            $query->whereYear('entry_date', (int) $line->budget->budget_year);
        }

        if ($line->dimension_id !== null) {
            $query->where('dimension_id', $line->dimension_id);
        }

        $debit = '0.000000';
        $credit = '0.000000';
        foreach ($query->get(['debit', 'credit']) as $entry) {
            $debit = $this->math->add($debit, (string) $entry->debit);
            $credit = $this->math->add($credit, (string) $entry->credit);
        }

        $normal = $account->normal_balance instanceof NormalBalance
            ? $account->normal_balance
            : NormalBalance::from((string) $account->normal_balance);

        return $normal === NormalBalance::Debit
            ? $this->math->sub($debit, $credit)
            : $this->math->sub($credit, $debit);
    }
}

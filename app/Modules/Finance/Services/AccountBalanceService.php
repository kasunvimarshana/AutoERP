<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\AccountBalanceResult;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountBalance;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceJournalLine;

final class AccountBalanceService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function applyJournalLine(FinanceJournalEntry $journal, FinanceJournalLine $line): FinanceAccountBalance
    {
        $account = $line->account;
        $balance = FinanceAccountBalance::query()->firstOrNew([
            'tenant_id' => $journal->tenant_id,
            'organization_unit_id' => $journal->organization_unit_id,
            'account_id' => $line->account_id,
            'fiscal_period_id' => $journal->fiscal_period_id,
        ]);

        if (! $balance->exists) {
            $balance->forceFill([
                'fiscal_year_id' => $journal->fiscal_year_id,
                'opening_debit' => '0.000000',
                'opening_credit' => '0.000000',
                'period_debit' => '0.000000',
                'period_credit' => '0.000000',
                'closing_debit' => '0.000000',
                'closing_credit' => '0.000000',
            ]);
        }

        $balance->period_debit = $this->math->add((string) $balance->period_debit, (string) $line->debit);
        $balance->period_credit = $this->math->add((string) $balance->period_credit, (string) $line->credit);
        $this->syncClosing($balance, $account);
        $balance->save();

        return $balance->refresh();
    }

    public function result(FinanceAccountBalance $balance): AccountBalanceResult
    {
        $account = $balance->account;
        $normalBalance = $account->normal_balance instanceof NormalBalance
            ? $account->normal_balance
            : NormalBalance::from((string) $account->normal_balance);

        return new AccountBalanceResult(
            accountId: (int) $account->getKey(),
            normalBalance: $normalBalance,
            openingDebit: (string) $balance->opening_debit,
            openingCredit: (string) $balance->opening_credit,
            periodDebit: (string) $balance->period_debit,
            periodCredit: (string) $balance->period_credit,
            closingDebit: (string) $balance->closing_debit,
            closingCredit: (string) $balance->closing_credit,
            balance: $this->accountBalanceAmount(
                $normalBalance,
                (string) $balance->closing_debit,
                (string) $balance->closing_credit,
            ),
        );
    }

    public function accountBalanceAfter(FinanceAccount $account, string $debit, string $credit): string
    {
        $normalBalance = $account->normal_balance instanceof NormalBalance
            ? $account->normal_balance
            : NormalBalance::from((string) $account->normal_balance);

        $current = $this->math->normalize((string) $account->current_balance);

        return $normalBalance === NormalBalance::Debit
            ? $this->math->sub($this->math->add($current, $debit), $credit)
            : $this->math->sub($this->math->add($current, $credit), $debit);
    }

    private function syncClosing(FinanceAccountBalance $balance, FinanceAccount $account): void
    {
        $debit = $this->math->add((string) $balance->opening_debit, (string) $balance->period_debit);
        $credit = $this->math->add((string) $balance->opening_credit, (string) $balance->period_credit);

        $normalBalance = $account->normal_balance instanceof NormalBalance
            ? $account->normal_balance
            : NormalBalance::from((string) $account->normal_balance);

        if ($normalBalance === NormalBalance::Debit) {
            $net = $this->math->sub($debit, $credit);
            $balance->closing_debit = $this->math->compare($net, '0') >= 0 ? $net : '0.000000';
            $balance->closing_credit = $this->math->compare($net, '0') < 0 ? ltrim($net, '-') : '0.000000';

            return;
        }

        $net = $this->math->sub($credit, $debit);
        $balance->closing_credit = $this->math->compare($net, '0') >= 0 ? $net : '0.000000';
        $balance->closing_debit = $this->math->compare($net, '0') < 0 ? ltrim($net, '-') : '0.000000';
    }

    private function accountBalanceAmount(NormalBalance $normalBalance, string $closingDebit, string $closingCredit): string
    {
        return $normalBalance === NormalBalance::Debit
            ? $this->math->sub($closingDebit, $closingCredit)
            : $this->math->sub($closingCredit, $closingDebit);
    }
}

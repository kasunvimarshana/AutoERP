<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\AccountBalanceResult;
use Modules\Finance\Enums\JournalType;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountBalance;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinanceJournalLine;
use Modules\Finance\Models\FinanceLedgerEntry;

final class AccountBalanceService
{
    private const ZERO_AMOUNT = '0.000000';

    public function __construct(private readonly DecimalMath $math) {}

    public function applyJournalLine(FinanceJournalEntry $journal, FinanceJournalLine $line): FinanceAccountBalance
    {
        $account = $line->account;
        $balance = FinanceAccountBalance::query()->firstOrNew([
            'tenant_id' => $journal->tenant_id,
            'organization_unit_id' => $journal->organization_unit_id,
            'account_id' => $line->account_id,
        ]);

        if (! $balance->exists) {
            $balance->forceFill([
                'opening_debit' => self::ZERO_AMOUNT,
                'opening_credit' => self::ZERO_AMOUNT,
                'period_debit' => self::ZERO_AMOUNT,
                'period_credit' => self::ZERO_AMOUNT,
                'closing_debit' => self::ZERO_AMOUNT,
                'closing_credit' => self::ZERO_AMOUNT,
            ]);
        }

        if ($this->isOpeningJournal($journal)) {
            $balance->opening_debit = $this->math->add((string) $balance->opening_debit, (string) $line->debit);
            $balance->opening_credit = $this->math->add((string) $balance->opening_credit, (string) $line->credit);
        } else {
            $balance->period_debit = $this->math->add((string) $balance->period_debit, (string) $line->debit);
            $balance->period_credit = $this->math->add((string) $balance->period_credit, (string) $line->credit);
        }

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
            accountCode: (string) $account->code,
            accountName: (string) $account->name,
        );
    }

    public function forDateRange(
        FinanceAccount $account,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): AccountBalanceResult {
        return $this->forAccounts(collect([$account]), $dateFrom, $dateTo)[0];
    }

    /**
     * @param  Collection<int, FinanceAccount>  $accounts
     * @return list<AccountBalanceResult>
     */
    public function forAccounts(
        Collection $accounts,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        if ($accounts->isEmpty()) {
            return [];
        }

        $query = FinanceLedgerEntry::query()
            ->whereIn('account_id', $accounts->pluck('id')->all())
            ->orderBy('entry_date')
            ->orderBy('id');

        if ($dateTo !== null) {
            $query->whereDate('entry_date', '<=', $dateTo);
        }

        $entries = $query->get(['account_id', 'entry_date', 'debit', 'credit'])->groupBy('account_id');
        $results = [];

        foreach ($accounts as $account) {
            $normalBalance = $account->normal_balance instanceof NormalBalance
                ? $account->normal_balance
                : NormalBalance::from((string) $account->normal_balance);
            $openingDebit = self::ZERO_AMOUNT;
            $openingCredit = self::ZERO_AMOUNT;
            $periodDebit = self::ZERO_AMOUNT;
            $periodCredit = self::ZERO_AMOUNT;

            foreach ($entries->get($account->getKey(), collect()) as $entry) {
                $entryDate = $entry->entry_date->toDateString();
                if ($dateFrom !== null && $entryDate < $dateFrom) {
                    $openingDebit = $this->math->add($openingDebit, (string) $entry->debit);
                    $openingCredit = $this->math->add($openingCredit, (string) $entry->credit);

                    continue;
                }

                $periodDebit = $this->math->add($periodDebit, (string) $entry->debit);
                $periodCredit = $this->math->add($periodCredit, (string) $entry->credit);
            }

            [$openingDebit, $openingCredit] = $this->splitDebitCredit($openingDebit, $openingCredit);
            [$closingDebit, $closingCredit] = $this->splitDebitCredit(
                $this->math->add($openingDebit, $periodDebit),
                $this->math->add($openingCredit, $periodCredit),
            );

            $results[] = new AccountBalanceResult(
                accountId: (int) $account->getKey(),
                normalBalance: $normalBalance,
                openingDebit: $openingDebit,
                openingCredit: $openingCredit,
                periodDebit: $periodDebit,
                periodCredit: $periodCredit,
                closingDebit: $closingDebit,
                closingCredit: $closingCredit,
                balance: $this->accountBalanceAmount($normalBalance, $closingDebit, $closingCredit),
                accountCode: (string) $account->code,
                accountName: (string) $account->name,
            );
        }

        return $results;
    }

    private function isOpeningJournal(FinanceJournalEntry $journal): bool
    {
        $journalType = $journal->journal_type instanceof JournalType
            ? $journal->journal_type
            : JournalType::from((string) $journal->journal_type);

        return $journalType === JournalType::Opening;
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
            $balance->closing_debit = $this->math->compare($net, '0') >= 0 ? $net : self::ZERO_AMOUNT;
            $balance->closing_credit = $this->math->compare($net, '0') < 0 ? ltrim($net, '-') : self::ZERO_AMOUNT;

            return;
        }

        $net = $this->math->sub($credit, $debit);
        $balance->closing_credit = $this->math->compare($net, '0') >= 0 ? $net : self::ZERO_AMOUNT;
        $balance->closing_debit = $this->math->compare($net, '0') < 0 ? ltrim($net, '-') : self::ZERO_AMOUNT;
    }

    private function accountBalanceAmount(NormalBalance $normalBalance, string $closingDebit, string $closingCredit): string
    {
        return $normalBalance === NormalBalance::Debit
            ? $this->math->sub($closingDebit, $closingCredit)
            : $this->math->sub($closingCredit, $closingDebit);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitDebitCredit(string $debit, string $credit): array
    {
        $net = $this->math->sub($debit, $credit);

        return $this->math->compare($net, '0') >= 0
            ? [$net, self::ZERO_AMOUNT]
            : [self::ZERO_AMOUNT, ltrim($net, '-')];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Models\FinanceAccount;

final class LedgerBalanceProjectionService
{
    private const ZERO_AMOUNT = '0.000000';

    public function __construct(private readonly DecimalMath $math) {}

    /** @param list<int> $accountIds */
    public function rebuildForAccounts(array $accountIds): void
    {
        $accountIds = array_values(array_unique(array_filter(
            array_map(static fn (int $accountId): int => $accountId, $accountIds),
            static fn (int $accountId): bool => $accountId > 0,
        )));
        sort($accountIds);

        if ($accountIds === []) {
            return;
        }

        $accounts = FinanceAccount::query()
            ->whereIn('id', $accountIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($accountIds as $accountId) {
            $account = $accounts->get($accountId);
            if (! $account instanceof FinanceAccount) {
                continue;
            }

            $normalBalance = $account->normal_balance instanceof NormalBalance
                ? $account->normal_balance
                : NormalBalance::from((string) $account->normal_balance);
            $runningBalance = self::ZERO_AMOUNT;

            $entries = DB::table('finance_ledger_entries')
                ->where('tenant_id', (int) $account->tenant_id)
                ->where('account_id', $accountId)
                ->orderBy('entry_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'debit', 'credit']);

            foreach ($entries as $entry) {
                $debit = $this->math->normalize((string) $entry->debit);
                $credit = $this->math->normalize((string) $entry->credit);
                $runningBalance = $normalBalance === NormalBalance::Debit
                    ? $this->math->sub($this->math->add($runningBalance, $debit), $credit)
                    : $this->math->sub($this->math->add($runningBalance, $credit), $debit);

                DB::table('finance_ledger_entries')
                    ->where('id', (int) $entry->id)
                    ->update([
                        'balance_after' => $runningBalance,
                        'updated_at' => now(),
                    ]);
            }
        }
    }
}

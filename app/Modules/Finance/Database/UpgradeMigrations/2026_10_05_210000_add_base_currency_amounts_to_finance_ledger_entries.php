<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Enums\NormalBalance;
use Modules\Core\Support\TenantExecutionContext;

return new class extends Migration
{
    private const TABLE = 'finance_ledger_entries';

    public function up(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'base_debit')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->decimal('base_debit', 20, 6)->default('0');
            });
        }
        if (! Schema::hasColumn(self::TABLE, 'base_credit')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->decimal('base_credit', 20, 6)->default('0');
            });
        }

        DB::transaction(function (): void {
            $this->backfillBaseAmounts();
            $this->rebuildBalances(useBaseAmounts: true);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $this->rebuildBalances(useBaseAmounts: false);
        });

        Schema::table(self::TABLE, function (Blueprint $table): void {
            if (Schema::hasColumn(self::TABLE, 'base_credit')) {
                $table->dropColumn('base_credit');
            }
            if (Schema::hasColumn(self::TABLE, 'base_debit')) {
                $table->dropColumn('base_debit');
            }
        });
    }

    private function backfillBaseAmounts(): void
    {
        $math = new DecimalMath();

        DB::table(self::TABLE.' as ledger')
            ->join('finance_journal_entries as journal', 'journal.id', '=', 'ledger.journal_entry_id')
            ->orderBy('ledger.id')
            ->select(['ledger.id', 'ledger.debit', 'ledger.credit', 'journal.exchange_rate'])
            ->chunkById(250, function ($rows) use ($math): void {
                foreach ($rows as $row) {
                    DB::table(self::TABLE)
                        ->where('id', (int) $row->id)
                        ->update([
                            'base_debit' => $math->mul((string) $row->debit, (string) $row->exchange_rate),
                            'base_credit' => $math->mul((string) $row->credit, (string) $row->exchange_rate),
                            'updated_at' => now(),
                        ]);
                }
            }, 'ledger.id', 'id');
    }

    private function rebuildBalances(bool $useBaseAmounts): void
    {
        app(TenantExecutionContext::class)->runAsControlPlane(function () use ($useBaseAmounts): void {
            $math = new DecimalMath();
            $debitColumn = $useBaseAmounts ? 'base_debit' : 'debit';
            $creditColumn = $useBaseAmounts ? 'base_credit' : 'credit';

            $accountIds = DB::table(self::TABLE)
                ->distinct()
                ->orderBy('account_id')
                ->pluck('account_id');

            foreach ($accountIds as $accountId) {
                $account = DB::table('finance_accounts')
                    ->where('id', (int) $accountId)
                    ->first(['normal_balance']);
                if ($account === null) {
                    continue;
                }

                $normalBalance = NormalBalance::from((string) $account->normal_balance);
                $runningBalance = '0.000000';
                $entries = DB::table(self::TABLE)
                    ->where('account_id', (int) $accountId)
                    ->orderBy('entry_date')
                    ->orderBy('id')
                    ->get(['id', $debitColumn, $creditColumn]);

                foreach ($entries as $entry) {
                    $debit = $math->normalize((string) $entry->{$debitColumn});
                    $credit = $math->normalize((string) $entry->{$creditColumn});
                    $runningBalance = $normalBalance === NormalBalance::Debit
                        ? $math->sub($math->add($runningBalance, $debit), $credit)
                        : $math->sub($math->add($runningBalance, $credit), $debit);

                    DB::table(self::TABLE)
                        ->where('id', (int) $entry->id)
                        ->update([
                            'balance_after' => $runningBalance,
                            'updated_at' => now(),
                        ]);
                }
            }
        }
        });
    }

};

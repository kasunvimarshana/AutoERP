<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->rebuild(useBaseAmounts: true);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $this->rebuild(useBaseAmounts: false);
        });
    }

    private function rebuild(bool $useBaseAmounts): void
    {
        $math = new DecimalMath();
        $debitColumn = $useBaseAmounts ? 'ledger.base_debit' : 'ledger.debit';
        $creditColumn = $useBaseAmounts ? 'ledger.base_credit' : 'ledger.credit';

        DB::table('finance_account_balances')->delete();

        $rows = DB::table('finance_ledger_entries as ledger')
            ->join('finance_journal_entries as journal', 'journal.id', '=', 'ledger.journal_entry_id')
            ->orderBy('ledger.account_id')
            ->orderBy('ledger.entry_date')
            ->orderBy('ledger.id')
            ->get([
                'ledger.tenant_id',
                'ledger.organization_unit_id',
                'ledger.account_id',
                DB::raw($debitColumn.' as debit_amount'),
                DB::raw($creditColumn.' as credit_amount'),
                'journal.journal_type',
            ]);

        $balances = [];
        foreach ($rows as $row) {
            $key = (string) $row->tenant_id.':'.(string) $row->account_id;
            $balances[$key] ??= [
                'tenant_id' => (int) $row->tenant_id,
                'organization_unit_id' => $row->organization_unit_id === null ? null : (int) $row->organization_unit_id,
                'account_id' => (int) $row->account_id,
                'opening_debit' => '0.000000',
                'opening_credit' => '0.000000',
                'period_debit' => '0.000000',
                'period_credit' => '0.000000',
            ];

            $opening = (string) $row->journal_type === 'opening';
            $debitKey = $opening ? 'opening_debit' : 'period_debit';
            $creditKey = $opening ? 'opening_credit' : 'period_credit';
            $balances[$key][$debitKey] = $math->add($balances[$key][$debitKey], (string) $row->debit_amount);
            $balances[$key][$creditKey] = $math->add($balances[$key][$creditKey], (string) $row->credit_amount);
        }

        foreach ($balances as $balance) {
            $debit = $math->add($balance['opening_debit'], $balance['period_debit']);
            $credit = $math->add($balance['opening_credit'], $balance['period_credit']);
            $net = $math->sub($debit, $credit);
            $closingDebit = $math->compare($net, '0') >= 0 ? $net : '0.000000';
            $closingCredit = $math->compare($net, '0') < 0 ? ltrim($net, '-') : '0.000000';

            DB::table('finance_account_balances')->insert([
                ...$balance,
                'closing_debit' => $closingDebit,
                'closing_credit' => $closingCredit,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};

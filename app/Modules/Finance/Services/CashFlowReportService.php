<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;

final class CashFlowReportService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @return array<string, mixed>
     */
    public function calculate(
        int $tenantId,
        ?int $organizationUnitId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $query = DB::table('finance_ledger_entries')
            ->join('finance_accounts', 'finance_accounts.id', '=', 'finance_ledger_entries.account_id')
            ->where('finance_ledger_entries.tenant_id', $tenantId)
            ->where(fn ($scope) => $scope
                ->where('finance_accounts.is_cash_account', true)
                ->orWhere('finance_accounts.is_bank_account', true))
            ->select([
                'finance_ledger_entries.entry_date',
                'finance_ledger_entries.source_module',
                'finance_ledger_entries.source_type',
                'finance_ledger_entries.source_number',
                'finance_ledger_entries.debit',
                'finance_ledger_entries.credit',
                'finance_accounts.id as account_id',
                'finance_accounts.code as account_code',
                'finance_accounts.name as account_name',
            ])
            ->orderBy('finance_ledger_entries.entry_date')
            ->orderBy('finance_ledger_entries.id');

        $organizationUnitId === null
            ? $query->whereNull('finance_ledger_entries.organization_unit_id')
            : $query->where('finance_ledger_entries.organization_unit_id', $organizationUnitId);
        if ($dateFrom !== null) {
            $query->whereDate('finance_ledger_entries.entry_date', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $query->whereDate('finance_ledger_entries.entry_date', '<=', $dateTo);
        }

        $inflow = '0.000000';
        $outflow = '0.000000';
        $rows = [];

        foreach ($query->get() as $entry) {
            $debit = $this->math->normalize((string) $entry->debit);
            $credit = $this->math->normalize((string) $entry->credit);
            $inflow = $this->math->add($inflow, $debit);
            $outflow = $this->math->add($outflow, $credit);
            $rows[] = [
                'entry_date' => (string) $entry->entry_date,
                'account_id' => (int) $entry->account_id,
                'account_code' => (string) $entry->account_code,
                'account_name' => (string) $entry->account_name,
                'source_module' => $entry->source_module,
                'source_type' => $entry->source_type,
                'source_number' => $entry->source_number,
                'inflow' => $debit,
                'outflow' => $credit,
                'net_flow' => $this->math->sub($debit, $credit),
            ];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_inflow' => $inflow,
            'total_outflow' => $outflow,
            'net_cash_flow' => $this->math->sub($inflow, $outflow),
            'rows' => $rows,
        ];
    }
}

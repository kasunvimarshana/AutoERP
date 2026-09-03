<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;
use Modules\Purchase\Enums\PurchaseReturnStatus;

final class GrnPayablesReportQuery
{
    private const GOODS_RECEIPT_SOURCE = 'goods_receipt_note';

    private const GOODS_RECEIPT_LINE_SOURCE = 'goods_receipt_note_line';

    private const ACTIVE_LINK_STATUS = 'active';

    private const FINAL_INVOICE_STATUSES = [
        InvoiceStatus::Posted->value,
        InvoiceStatus::PartiallyPaid->value,
        InvoiceStatus::Paid->value,
    ];

    private const OPEN_INVOICE_STATUSES = [
        InvoiceStatus::Draft->value,
        InvoiceStatus::Approved->value,
        InvoiceStatus::Posted->value,
        InvoiceStatus::PartiallyPaid->value,
        InvoiceStatus::Paid->value,
    ];

    /** @param array<string, mixed> $params */
    public function build(array $params): Builder
    {
        $invoiceTotals = DB::table('purchase_invoice_links')
            ->where('tenant_id', (int) $params['tenant_id'])
            ->where('status', self::ACTIVE_LINK_STATUS)
            ->groupBy('invoice_id')
            ->select('invoice_id')
            ->selectRaw('COALESCE(SUM(invoice_total), 0) as source_invoice_total');
        $this->organizationScope($invoiceTotals, 'organization_unit_id', $params['organization_unit_id'] ?? null);

        $invoiceLinks = DB::table('purchase_invoice_links as links')
            ->join('invoices', 'invoices.id', '=', 'links.invoice_id')
            ->joinSub($invoiceTotals, 'invoice_totals', fn ($join) => $join->on('invoice_totals.invoice_id', '=', 'links.invoice_id'))
            ->where('links.tenant_id', (int) $params['tenant_id'])
            ->where('invoices.tenant_id', (int) $params['tenant_id'])
            ->where('links.status', self::ACTIVE_LINK_STATUS)
            ->where('links.source_type', self::GOODS_RECEIPT_SOURCE)
            ->whereIn('invoices.status', self::OPEN_INVOICE_STATUSES)
            ->whereNull('invoices.deleted_at')
            ->groupBy('links.source_id')
            ->select('links.source_id')
            ->selectRaw('COUNT(DISTINCT links.invoice_id) as invoice_count')
            ->selectRaw('COALESCE(SUM(links.invoice_total), 0) as linked_invoice_amount')
            ->selectRaw($this->invoiceStatusSumSql(self::FINAL_INVOICE_STATUSES).' as finalized_invoice_amount')
            ->selectRaw($this->invoiceStatusSumSql([InvoiceStatus::Draft->value, InvoiceStatus::Approved->value]).' as pending_invoice_amount')
            ->selectRaw($this->allocatedInvoiceBalanceSql().' as ap_outstanding')
            ->selectRaw('('.$this->invoiceStatusSumSql(self::FINAL_INVOICE_STATUSES).' - '.$this->allocatedInvoiceBalanceSql().') as settled_invoice_amount');
        $this->organizationScope($invoiceLinks, 'links.organization_unit_id', $params['organization_unit_id'] ?? null);
        $this->organizationScope($invoiceLinks, 'invoices.organization_unit_id', $params['organization_unit_id'] ?? null);

        $lineProgress = DB::table('goods_receipt_note_lines')
            ->where('tenant_id', (int) $params['tenant_id'])
            ->groupBy('goods_receipt_note_id')
            ->select('goods_receipt_note_id')
            ->selectRaw('COALESCE(SUM(accepted_quantity), 0) as accepted_quantity')
            ->selectRaw('COALESCE(SUM(invoiced_quantity), 0) as invoiced_quantity')
            ->selectRaw('COALESCE(SUM(returned_quantity), 0) as returned_quantity');
        $this->organizationScope($lineProgress, 'organization_unit_id', $params['organization_unit_id'] ?? null);

        $returnCredits = DB::table('purchase_returns as returns')
            ->leftJoin('purchase_debit_notes as debit_notes', 'debit_notes.id', '=', 'returns.debit_note_id')
            ->where('returns.tenant_id', (int) $params['tenant_id'])
            ->where('returns.source_type', self::GOODS_RECEIPT_SOURCE)
            ->where('returns.status', PurchaseReturnStatus::Posted->value)
            ->whereNull('returns.deleted_at')
            ->groupBy('returns.source_id')
            ->select('returns.source_id')
            ->selectRaw('COUNT(*) as return_count')
            ->selectRaw('COALESCE(SUM(returns.grand_total), 0) as return_amount')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN debit_notes.status = ? THEN debit_notes.remaining_amount ELSE 0 END), 0) as open_return_credit',
                [PurchaseDebitNoteStatus::Posted->value],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN debit_notes.id IS NULL OR debit_notes.status <> ? THEN returns.grand_total ELSE 0 END), 0) as pending_return_credit',
                [PurchaseDebitNoteStatus::Posted->value],
            );
        $this->organizationScope($returnCredits, 'returns.organization_unit_id', $params['organization_unit_id'] ?? null);

        $grni = $this->grniBalances((int) $params['tenant_id'], $params['organization_unit_id'] ?? null);
        $linked = 'COALESCE(invoice_links.linked_invoice_amount, 0)';
        $uninvoiced = '(CASE WHEN grns.grand_total > '.$linked.' THEN grns.grand_total - '.$linked.' ELSE 0 END)';
        $outstanding = 'COALESCE(invoice_links.ap_outstanding, 0)';
        $openCredit = 'COALESCE(return_credits.open_return_credit, 0)';
        $projectedExposure = '('.$uninvoiced.' + '.$outstanding.' - '.$openCredit.')';
        $accepted = 'COALESCE(line_progress.accepted_quantity, 0)';
        $invoiced = 'COALESCE(line_progress.invoiced_quantity, 0)';
        $invoiceProgress = '(CASE WHEN '.$accepted.' <= 0 OR '.$invoiced.' <= 0 THEN \'not_invoiced\' WHEN '.$invoiced.' >= '.$accepted.' THEN \'invoiced\' ELSE \'partially_invoiced\' END)';
        $exposureStatus = '(CASE WHEN '.$projectedExposure.' > 0 THEN \'open\' WHEN '.$projectedExposure.' < 0 THEN \'credit\' ELSE \'settled\' END)';

        $query = DB::table('goods_receipt_notes as grns')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'grns.supplier_id')
            ->leftJoinSub($lineProgress, 'line_progress', fn ($join) => $join->on('line_progress.goods_receipt_note_id', '=', 'grns.id'))
            ->leftJoinSub($invoiceLinks, 'invoice_links', fn ($join) => $join->on('invoice_links.source_id', '=', 'grns.id'))
            ->leftJoinSub($returnCredits, 'return_credits', fn ($join) => $join->on('return_credits.source_id', '=', 'grns.id'))
            ->leftJoinSub($grni, 'grni', fn ($join) => $join->on('grni.grn_id', '=', 'grns.id'))
            ->where('grns.tenant_id', (int) $params['tenant_id'])
            ->where('grns.status', GoodsReceiptNoteStatus::Posted->value)
            ->whereNull('grns.deleted_at')
            ->select([
                'grns.id',
                'grns.received_date',
                'grns.grn_number',
                'grns.supplier_id',
                'suppliers.code as supplier_code',
                'suppliers.name as supplier_name',
            ])
            ->selectRaw($accepted.' as accepted_quantity')
            ->selectRaw($invoiced.' as invoiced_quantity')
            ->selectRaw('COALESCE(line_progress.returned_quantity, 0) as returned_quantity')
            ->selectRaw($invoiceProgress.' as invoice_progress')
            ->selectRaw('COALESCE(invoice_links.invoice_count, 0) as invoice_count')
            ->selectRaw('grns.grand_total as receipt_total')
            ->selectRaw($linked.' as linked_invoice_amount')
            ->selectRaw('COALESCE(invoice_links.finalized_invoice_amount, 0) as finalized_invoice_amount')
            ->selectRaw('COALESCE(invoice_links.pending_invoice_amount, 0) as pending_invoice_amount')
            ->selectRaw($uninvoiced.' as uninvoiced_amount')
            ->selectRaw('COALESCE(invoice_links.settled_invoice_amount, 0) as settled_invoice_amount')
            ->selectRaw($outstanding.' as ap_outstanding')
            ->selectRaw('COALESCE(return_credits.return_count, 0) as return_count')
            ->selectRaw('COALESCE(return_credits.return_amount, 0) as return_amount')
            ->selectRaw($openCredit.' as open_return_credit')
            ->selectRaw('COALESCE(return_credits.pending_return_credit, 0) as pending_return_credit')
            ->selectRaw($projectedExposure.' as projected_exposure')
            ->selectRaw('COALESCE(grni.grni_balance, 0) as grni_balance')
            ->selectRaw($exposureStatus.' as exposure_status');

        $this->organizationScope($query, 'grns.organization_unit_id', $params['organization_unit_id'] ?? null);
        $this->filters($query, $params, $invoiceProgress, $exposureStatus);

        return $query;
    }

    /** @param array<string, mixed> $params */
    public function sort(Builder $query, array $params): void
    {
        $columns = [
            'received_date' => 'received_date', 'grn_number' => 'grn_number', 'supplier' => 'supplier_name',
            'invoice_progress' => 'invoice_progress', 'invoice_count' => 'invoice_count', 'receipt_total' => 'receipt_total',
            'linked_invoice_amount' => 'linked_invoice_amount', 'uninvoiced_amount' => 'uninvoiced_amount',
            'settled_invoice_amount' => 'settled_invoice_amount', 'ap_outstanding' => 'ap_outstanding',
            'open_return_credit' => 'open_return_credit', 'projected_exposure' => 'projected_exposure',
            'grni_balance' => 'grni_balance', 'exposure_status' => 'exposure_status',
        ];
        $sort = (string) ($params['sort'] ?? 'received_date');
        $direction = (string) ($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sort === 'age_days') {
            $query->orderBy('received_date', $direction === 'asc' ? 'desc' : 'asc');
        } else {
            $query->orderBy($columns[$sort] ?? $columns['received_date'], $direction);
        }
        $query->orderByDesc('grns.id');
    }

    /** @param array<string, mixed> $params */
    private function filters(Builder $query, array $params, string $invoiceProgress, string $exposureStatus): void
    {
        if (! empty($params['date_from'])) {
            $query->whereDate('grns.received_date', '>=', (string) $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('grns.received_date', '<=', (string) $params['date_to']);
        }
        if (! empty($params['supplier_id'])) {
            $query->where('grns.supplier_id', (int) $params['supplier_id']);
        }
        if (! empty($params['invoice_progress'])) {
            $query->whereRaw($invoiceProgress.' = ?', [(string) $params['invoice_progress']]);
        }
        if (! empty($params['exposure_status'])) {
            $query->whereRaw($exposureStatus.' = ?', [(string) $params['exposure_status']]);
        }

        $search = $this->searchTerm($params['search'] ?? null);
        if ($search !== null) {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('grns.grn_number', 'like', $search)
                    ->orWhere('suppliers.code', 'like', $search)
                    ->orWhere('suppliers.name', 'like', $search);
            });
        }
    }

    private function grniBalances(int $tenantId, mixed $organizationUnitId): Builder
    {
        $grniAccounts = DB::table('finance_account_assignments as assignments')
            ->join('finance_account_roles as roles', 'roles.id', '=', 'assignments.account_role_id')
            ->where('assignments.tenant_id', $tenantId)
            ->where('roles.tenant_id', $tenantId)
            ->where('roles.code', FinanceAccountRoleCode::GoodsReceivedNotInvoiced->value)
            ->select('assignments.account_id')
            ->distinct();

        $receiptMovements = DB::table('finance_ledger_entries as ledger')
            ->where('ledger.tenant_id', $tenantId)
            ->where('ledger.source_type', self::GOODS_RECEIPT_SOURCE)
            ->whereIn('ledger.account_id', $grniAccounts)
            ->selectRaw('ledger.source_id as grn_id, SUM(ledger.credit - ledger.debit) as amount')
            ->groupBy('ledger.source_id');
        $this->organizationScope($receiptMovements, 'ledger.organization_unit_id', $organizationUnitId);

        $invoiceMovements = DB::table('finance_ledger_entries as ledger')
            ->join('goods_receipt_note_lines as grn_lines', function ($join): void {
                $join->on('grn_lines.id', '=', 'ledger.source_line_id')
                    ->where('ledger.source_line_type', self::GOODS_RECEIPT_LINE_SOURCE);
            })
            ->where('ledger.tenant_id', $tenantId)
            ->where('grn_lines.tenant_id', $tenantId)
            ->whereIn('ledger.account_id', $grniAccounts)
            ->selectRaw('grn_lines.goods_receipt_note_id as grn_id, SUM(ledger.credit - ledger.debit) as amount')
            ->groupBy('grn_lines.goods_receipt_note_id');
        $this->organizationScope($invoiceMovements, 'ledger.organization_unit_id', $organizationUnitId);
        $this->organizationScope($invoiceMovements, 'grn_lines.organization_unit_id', $organizationUnitId);

        return DB::query()
            ->fromSub($receiptMovements->unionAll($invoiceMovements), 'grni_movements')
            ->select('grn_id')
            ->selectRaw('COALESCE(SUM(amount), 0) as grni_balance')
            ->groupBy('grn_id');
    }

    private function allocatedInvoiceBalanceSql(): string
    {
        return 'COALESCE(SUM(CASE WHEN invoices.status IN ('.$this->quotedStatuses(self::FINAL_INVOICE_STATUSES).') AND invoice_totals.source_invoice_total > 0 '
            .'THEN invoices.balance_due * links.invoice_total / invoice_totals.source_invoice_total ELSE 0 END), 0)';
    }

    /** @param list<string> $statuses */
    private function invoiceStatusSumSql(array $statuses): string
    {
        return 'COALESCE(SUM(CASE WHEN invoices.status IN ('.$this->quotedStatuses($statuses).') THEN links.invoice_total ELSE 0 END), 0)';
    }

    /** @param list<string> $statuses */
    private function quotedStatuses(array $statuses): string
    {
        return implode(', ', array_map(static fn (string $status): string => DB::getPdo()->quote($status), $statuses));
    }

    private function organizationScope(Builder $query, string $column, mixed $organizationUnitId): void
    {
        $id = $organizationUnitId === null || $organizationUnitId === '' ? null : (int) $organizationUnitId;
        $id === null ? $query->whereNull($column) : $query->where($column, $id);
    }

    private function searchTerm(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : '%'.str_replace(['%', '_'], ' ', $value).'%';
    }
}

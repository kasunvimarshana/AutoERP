<?php

declare(strict_types=1);

namespace Modules\Voucher\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class VoucherQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $union = $this->paymentRows($tenantId, $organizationUnitId, $filters)
            ->unionAll($this->financeJournalRows($tenantId, $organizationUnitId))
            ->unionAll($this->paymentReversalRows($tenantId, $organizationUnitId));

        $query = DB::query()->fromSub($union, 'voucher_rows');

        foreach (['voucher_type', 'source_module', 'source_kind', 'document_status', 'allocation_status', 'posting_status', 'instrument_status'] as $filter) {
            if (($filters[$filter] ?? null) !== null && $filters[$filter] !== '') {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (($filters['search'] ?? null) !== null && trim((string) $filters['search']) !== '') {
            $search = '%'.trim((string) $filters['search']).'%';
            $query->where(function ($scope) use ($search): void {
                $scope->where('voucher_number', 'like', $search)
                    ->orWhere('source_document_number', 'like', $search)
                    ->orWhere('party_or_narration', 'like', $search)
                    ->orWhere('narration', 'like', $search);
            });
        }

        if (($filters['party'] ?? null) !== null && trim((string) $filters['party']) !== '') {
            $query->where('party_or_narration', 'like', '%'.trim((string) $filters['party']).'%');
        }

        if (($filters['date_from'] ?? null) !== null) {
            $query->whereDate('voucher_date', '>=', $filters['date_from']);
        }
        if (($filters['date_to'] ?? null) !== null) {
            $query->whereDate('voucher_date', '<=', $filters['date_to']);
        }
        if (($filters['amount_min'] ?? null) !== null) {
            $query->where('transaction_amount', '>=', $filters['amount_min']);
        }
        if (($filters['amount_max'] ?? null) !== null) {
            $query->where('transaction_amount', '<=', $filters['amount_max']);
        }

        $sortMap = [
            'voucher_date' => 'voucher_date',
            'voucher_number' => 'voucher_number',
            'voucher_type' => 'voucher_type',
            'amount' => 'transaction_amount',
            'document_status' => 'document_status',
            'created_at' => 'created_at',
        ];
        $sort = $sortMap[(string) ($filters['sort'] ?? 'voucher_date')] ?? 'voucher_date';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('source_id', $direction)
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function paymentRows(int $tenantId, ?int $organizationUnitId, array $filters)
    {
        $query = DB::table('payments')
            ->leftJoin('currencies', 'currencies.id', '=', 'payments.currency_id')
            ->where('payments.tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn ($scope) => $scope->whereNull('payments.organization_unit_id'))
            ->when($organizationUnitId !== null, fn ($scope) => $scope->where('payments.organization_unit_id', $organizationUnitId));

        if (($filters['payment_method'] ?? null) !== null && trim((string) $filters['payment_method']) !== '') {
            $method = trim((string) $filters['payment_method']);
            $query->whereExists(function ($exists) use ($method): void {
                $exists->selectRaw('1')
                    ->from('payment_lines')
                    ->leftJoin('payment_methods', 'payment_methods.id', '=', 'payment_lines.payment_method_id')
                    ->whereColumn('payment_lines.payment_id', 'payments.id')
                    ->where(function ($scope) use ($method): void {
                        $scope->where('payment_methods.method_type', $method)
                            ->orWhere('payment_methods.code', $method);
                    });
            });
        }

        return $query->select([
            DB::raw("CASE WHEN payments.direction = 'inbound' THEN 'receipt_voucher' ELSE 'payment_voucher' END as voucher_type"),
            'payments.payment_number as voucher_number',
            'payments.payment_date as voucher_date',
            DB::raw("'Payment' as source_module"),
            DB::raw("'payment' as source_kind"),
            DB::raw("'payment' as source_type"),
            'payments.id as source_id',
            'payments.payment_number as source_document_number',
            'payments.tenant_id',
            'payments.organization_unit_id',
            DB::raw('NULL as financial_year_id'),
            DB::raw('NULL as financial_period_id'),
            DB::raw('COALESCE(payments.payee_name, payments.notes, payments.party_type, payments.source_type) as party_or_narration'),
            'payments.party_type',
            'payments.party_id',
            'payments.currency_id',
            'currencies.code as currency_code',
            'payments.exchange_rate',
            'payments.total_amount as transaction_amount',
            DB::raw('NULL as base_currency_amount'),
            'payments.allocated_amount',
            'payments.unapplied_amount as unallocated_amount',
            'payments.document_status',
            'payments.status as approval_status',
            'payments.allocation_status',
            'payments.posting_status',
            'payments.instrument_status',
            'payments.notes as narration',
            'payments.reference_number as external_reference',
            DB::raw('NULL as source_document_url'),
            'payments.created_by',
            'payments.approved_by',
            DB::raw('NULL as posted_by'),
            'payments.created_at',
            'payments.updated_at',
        ]);
    }

    private function financeJournalRows(int $tenantId, ?int $organizationUnitId)
    {
        return DB::table('finance_journal_entries')
            ->leftJoin('currencies', 'currencies.id', '=', 'finance_journal_entries.currency_id')
            ->where('finance_journal_entries.tenant_id', $tenantId)
            ->whereIn('finance_journal_entries.journal_type', ['general', 'contra', 'adjustment', 'opening', 'reversal'])
            ->when($organizationUnitId === null, fn ($scope) => $scope->whereNull('finance_journal_entries.organization_unit_id'))
            ->when($organizationUnitId !== null, fn ($scope) => $scope->where('finance_journal_entries.organization_unit_id', $organizationUnitId))
            ->select([
                DB::raw("CASE finance_journal_entries.journal_type WHEN 'contra' THEN 'contra_voucher' WHEN 'adjustment' THEN 'adjustment_voucher' WHEN 'opening' THEN 'opening_balance_voucher' WHEN 'reversal' THEN 'reversal_voucher' ELSE 'journal_voucher' END as voucher_type"),
                'finance_journal_entries.journal_number as voucher_number',
                'finance_journal_entries.journal_date as voucher_date',
                DB::raw("'Finance' as source_module"),
                DB::raw("'finance_journal' as source_kind"),
                DB::raw("'finance_journal' as source_type"),
                'finance_journal_entries.id as source_id',
                'finance_journal_entries.source_number as source_document_number',
                'finance_journal_entries.tenant_id',
                'finance_journal_entries.organization_unit_id',
                'finance_journal_entries.fiscal_year_id as financial_year_id',
                'finance_journal_entries.fiscal_period_id as financial_period_id',
                'finance_journal_entries.description as party_or_narration',
                DB::raw('NULL as party_type'),
                DB::raw('NULL as party_id'),
                'finance_journal_entries.currency_id',
                'currencies.code as currency_code',
                'finance_journal_entries.exchange_rate',
                'finance_journal_entries.total_debit as transaction_amount',
                DB::raw('NULL as base_currency_amount'),
                DB::raw("'0.000000' as allocated_amount"),
                DB::raw("'0.000000' as unallocated_amount"),
                DB::raw("CASE WHEN finance_journal_entries.status = 'cancelled' THEN 'voided' WHEN finance_journal_entries.status = 'reversed' THEN 'reversed' ELSE 'approved' END as document_status"),
                'finance_journal_entries.status as approval_status',
                DB::raw('NULL as allocation_status'),
                DB::raw("CASE WHEN finance_journal_entries.status = 'posted' THEN 'posted' WHEN finance_journal_entries.status = 'reversed' THEN 'reversed' ELSE 'not_posted' END as posting_status"),
                DB::raw('NULL as instrument_status'),
                'finance_journal_entries.description as narration',
                'finance_journal_entries.source_number as external_reference',
                DB::raw('NULL as source_document_url'),
                'finance_journal_entries.created_by',
                DB::raw('NULL as approved_by'),
                'finance_journal_entries.posted_by',
                'finance_journal_entries.created_at',
                'finance_journal_entries.updated_at',
            ]);
    }

    private function paymentReversalRows(int $tenantId, ?int $organizationUnitId)
    {
        return DB::table('payment_reversals')
            ->join('payments', 'payments.id', '=', 'payment_reversals.payment_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'payments.currency_id')
            ->where('payment_reversals.tenant_id', $tenantId)
            ->when($organizationUnitId === null, fn ($scope) => $scope->whereNull('payment_reversals.organization_unit_id'))
            ->when($organizationUnitId !== null, fn ($scope) => $scope->where('payment_reversals.organization_unit_id', $organizationUnitId))
            ->select([
                DB::raw("'reversal_voucher' as voucher_type"),
                'payment_reversals.reversal_number as voucher_number',
                'payment_reversals.reversal_date as voucher_date',
                DB::raw("'Payment' as source_module"),
                DB::raw("'payment_reversal' as source_kind"),
                DB::raw("'payment_reversal' as source_type"),
                'payment_reversals.id as source_id',
                'payments.payment_number as source_document_number',
                'payment_reversals.tenant_id',
                'payment_reversals.organization_unit_id',
                DB::raw('NULL as financial_year_id'),
                DB::raw('NULL as financial_period_id'),
                DB::raw('COALESCE(payments.payee_name, payment_reversals.reason, payments.party_type) as party_or_narration'),
                'payments.party_type',
                'payments.party_id',
                'payments.currency_id',
                'currencies.code as currency_code',
                'payments.exchange_rate',
                'payment_reversals.reversed_amount as transaction_amount',
                DB::raw('NULL as base_currency_amount'),
                DB::raw("'0.000000' as allocated_amount"),
                DB::raw("'0.000000' as unallocated_amount"),
                DB::raw("'reversed' as document_status"),
                'payment_reversals.status as approval_status',
                DB::raw("'unallocated' as allocation_status"),
                DB::raw("'reversed' as posting_status"),
                DB::raw("'reversed' as instrument_status"),
                'payment_reversals.reason as narration',
                'payments.payment_number as external_reference',
                DB::raw('NULL as source_document_url'),
                'payment_reversals.reversed_by as created_by',
                DB::raw('NULL as approved_by'),
                DB::raw('NULL as posted_by'),
                'payment_reversals.created_at',
                'payment_reversals.updated_at',
            ]);
    }
}

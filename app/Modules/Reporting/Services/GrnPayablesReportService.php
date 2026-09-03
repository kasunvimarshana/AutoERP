<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;

final class GrnPayablesReportService
{
    public const REPORT_KEY = 'purchase/grn-payables';

    private const ZERO = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly OperationalReportResponseBuilder $responses,
        private readonly ReportBrandingResolver $branding,
        private readonly GrnPayablesReportQuery $queries,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function run(array $params): array
    {
        $query = $this->queries->build($params);
        $summary = $this->summary(clone $query);
        $suppliers = $this->supplierBreakdown(clone $query);
        $this->queries->sort($query, $params);

        $response = $this->responses->paginate(
            $query,
            fn (object $row): array => $this->row($row),
            $this->definition(),
            $summary,
            max(1, (int) ($params['page'] ?? 1)),
            min(100, max(1, (int) ($params['per_page'] ?? 25))),
        );
        $organizationUnit = $params['organization_unit_id'] ?? null;
        $organizationUnitId = $organizationUnit === null || $organizationUnit === ''
            ? null
            : (int) $organizationUnit;
        $brand = $this->branding->resolve((int) $params['tenant_id'], $organizationUnitId);

        return [
            ...$response,
            'currency_code' => (string) ($brand['currency_code'] ?? ''),
            'period' => [
                'date_from' => $params['date_from'] ?? null,
                'date_to' => $params['date_to'] ?? null,
            ],
            'suppliers' => $suppliers,
            'basis' => [
                'projected_exposure' => 'Expected uninvoiced GRN value plus allocated finalized invoice outstanding, less posted unallocated return credits.',
                'accounting_liability' => 'Current GRNI ledger balance plus allocated finalized invoice outstanding.',
                'invoice_allocation' => 'Invoice settlement and outstanding are allocated across linked source documents in proportion to each source invoice total.',
                'scope' => 'The date range filters GRN receipt dates; invoice, settlement, credit, and GRNI figures show their current state.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    public function exportRows(array $params): Collection
    {
        $query = $this->queries->build($params);
        $this->queries->sort($query, $params);

        return $this->responses->exportRows($query, fn (object $row): array => $this->row($row));
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: self::REPORT_KEY,
            title: 'GRN Payables & GRNI Report',
            group: 'Purchase',
            model: GoodsReceiptNote::class,
            columns: [
                new ReportColumn('received_date', 'Received', sortBy: 'received_date', format: 'date'),
                new ReportColumn('grn_number', 'GRN', sortBy: 'grn_number'),
                new ReportColumn('supplier', 'Supplier', sortBy: 'supplier'),
                new ReportColumn('age_days', 'Age (days)', sortBy: 'age_days', format: 'integer'),
                new ReportColumn('invoice_progress', 'Invoice progress', sortBy: 'invoice_progress', format: 'enum'),
                new ReportColumn('invoice_count', 'Invoices', sortBy: 'invoice_count', format: 'integer'),
                new ReportColumn('receipt_total', 'GRN total', sortBy: 'receipt_total', format: 'money', summarize: true),
                new ReportColumn('linked_invoice_amount', 'Linked invoice', sortBy: 'linked_invoice_amount', format: 'money', summarize: true),
                new ReportColumn('uninvoiced_amount', 'Expected uninvoiced', sortBy: 'uninvoiced_amount', format: 'money', summarize: true),
                new ReportColumn('settled_invoice_amount', 'Settled', sortBy: 'settled_invoice_amount', format: 'money', summarize: true),
                new ReportColumn('ap_outstanding', 'AP outstanding', sortBy: 'ap_outstanding', format: 'money', summarize: true),
                new ReportColumn('open_return_credit', 'Open return credit', sortBy: 'open_return_credit', format: 'money', summarize: true),
                new ReportColumn('projected_exposure', 'Projected exposure', sortBy: 'projected_exposure', format: 'money', summarize: true),
                new ReportColumn('grni_balance', 'GRNI balance', sortBy: 'grni_balance', format: 'money', summarize: true),
                new ReportColumn('exposure_status', 'Exposure', sortBy: 'exposure_status', format: 'enum'),
            ],
            dateColumn: 'received_date',
            defaultSort: 'received_date',
            defaultDirection: 'desc',
            description: 'Posted GRNs reconciled to supplier invoices, settlement, return credits, and the GRNI ledger.',
            orientation: 'landscape',
        );
    }

    /** @return array<string, int|string> */
    private function summary(Builder $query): array
    {
        $totals = DB::query()->fromSub($query, 'rows')->selectRaw(
            'COUNT(*) as grn_count, '
            .'COALESCE(SUM(CASE WHEN invoice_progress = \'not_invoiced\' THEN 1 ELSE 0 END), 0) as not_invoiced_count, '
            .'COALESCE(SUM(CASE WHEN invoice_progress = \'partially_invoiced\' THEN 1 ELSE 0 END), 0) as partially_invoiced_count, '
            .'COALESCE(SUM(CASE WHEN invoice_progress = \'invoiced\' THEN 1 ELSE 0 END), 0) as invoiced_count, '
            .'COALESCE(SUM(CASE WHEN exposure_status = \'open\' THEN 1 ELSE 0 END), 0) as open_exposure_count, '
            .'COALESCE(SUM(CASE WHEN open_return_credit > 0 THEN 1 ELSE 0 END), 0) as open_return_credit_count, '
            .'COALESCE(SUM(CASE WHEN invoice_progress = \'not_invoiced\' THEN uninvoiced_amount ELSE 0 END), 0) as not_invoiced_amount, '
            .'COALESCE(SUM(CASE WHEN invoice_progress = \'partially_invoiced\' THEN uninvoiced_amount ELSE 0 END), 0) as partially_invoiced_amount, '
            .'COALESCE(SUM(CASE WHEN invoice_progress = \'invoiced\' THEN ap_outstanding ELSE 0 END), 0) as invoiced_ap_outstanding, '
            .'COALESCE(SUM(receipt_total), 0) as receipt_total, COALESCE(SUM(linked_invoice_amount), 0) as linked_invoice_amount, '
            .'COALESCE(SUM(finalized_invoice_amount), 0) as finalized_invoice_amount, COALESCE(SUM(pending_invoice_amount), 0) as pending_invoice_amount, '
            .'COALESCE(SUM(uninvoiced_amount), 0) as uninvoiced_amount, COALESCE(SUM(settled_invoice_amount), 0) as settled_invoice_amount, '
            .'COALESCE(SUM(ap_outstanding), 0) as ap_outstanding, COALESCE(SUM(return_amount), 0) as return_amount, '
            .'COALESCE(SUM(open_return_credit), 0) as open_return_credit, COALESCE(SUM(pending_return_credit), 0) as pending_return_credit, '
            .'COALESCE(SUM(projected_exposure), 0) as projected_exposure, COALESCE(SUM(grni_balance), 0) as grni_balance'
        )->first();

        $grniBalance = $this->decimal($totals->grni_balance ?? 0);
        $apOutstanding = $this->decimal($totals->ap_outstanding ?? 0);

        return [
            'grn_count' => (int) ($totals->grn_count ?? 0),
            'not_invoiced_count' => (int) ($totals->not_invoiced_count ?? 0),
            'partially_invoiced_count' => (int) ($totals->partially_invoiced_count ?? 0),
            'invoiced_count' => (int) ($totals->invoiced_count ?? 0),
            'open_exposure_count' => (int) ($totals->open_exposure_count ?? 0),
            'open_return_credit_count' => (int) ($totals->open_return_credit_count ?? 0),
            'not_invoiced_amount' => $this->decimal($totals->not_invoiced_amount ?? 0),
            'partially_invoiced_amount' => $this->decimal($totals->partially_invoiced_amount ?? 0),
            'invoiced_ap_outstanding' => $this->decimal($totals->invoiced_ap_outstanding ?? 0),
            'receipt_total' => $this->decimal($totals->receipt_total ?? 0),
            'linked_invoice_amount' => $this->decimal($totals->linked_invoice_amount ?? 0),
            'finalized_invoice_amount' => $this->decimal($totals->finalized_invoice_amount ?? 0),
            'pending_invoice_amount' => $this->decimal($totals->pending_invoice_amount ?? 0),
            'uninvoiced_amount' => $this->decimal($totals->uninvoiced_amount ?? 0),
            'settled_invoice_amount' => $this->decimal($totals->settled_invoice_amount ?? 0),
            'ap_outstanding' => $apOutstanding,
            'return_amount' => $this->decimal($totals->return_amount ?? 0),
            'open_return_credit' => $this->decimal($totals->open_return_credit ?? 0),
            'pending_return_credit' => $this->decimal($totals->pending_return_credit ?? 0),
            'projected_exposure' => $this->decimal($totals->projected_exposure ?? 0),
            'grni_balance' => $grniBalance,
            'accounting_liability' => $this->math->add($grniBalance, $apOutstanding),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function supplierBreakdown(Builder $query): array
    {
        return DB::query()->fromSub($query, 'rows')
            ->select(['supplier_id', 'supplier_code', 'supplier_name'])
            ->selectRaw('COUNT(*) as grn_count, COALESCE(SUM(uninvoiced_amount), 0) as uninvoiced_amount')
            ->selectRaw('COALESCE(SUM(ap_outstanding), 0) as ap_outstanding, COALESCE(SUM(open_return_credit), 0) as open_return_credit')
            ->selectRaw('COALESCE(SUM(projected_exposure), 0) as projected_exposure, COALESCE(SUM(grni_balance), 0) as grni_balance')
            ->groupBy(['supplier_id', 'supplier_code', 'supplier_name'])
            ->orderByDesc('projected_exposure')
            ->limit(10)
            ->get()
            ->map(fn (object $row): array => [
                'supplier' => trim((string) ($row->supplier_code ?? '').' '.(string) ($row->supplier_name ?? '')),
                'grn_count' => (int) $row->grn_count,
                'uninvoiced_amount' => $this->decimal($row->uninvoiced_amount),
                'ap_outstanding' => $this->decimal($row->ap_outstanding),
                'open_return_credit' => $this->decimal($row->open_return_credit),
                'projected_exposure' => $this->decimal($row->projected_exposure),
                'grni_balance' => $this->decimal($row->grni_balance),
            ])->values()->all();
    }

    /** @return array<string, mixed> */
    private function row(object $row): array
    {
        $money = [
            'receipt_total', 'linked_invoice_amount', 'finalized_invoice_amount', 'pending_invoice_amount',
            'uninvoiced_amount', 'settled_invoice_amount', 'ap_outstanding', 'return_amount',
            'open_return_credit', 'pending_return_credit', 'projected_exposure', 'grni_balance',
        ];
        $result = [
            'id' => (int) $row->id,
            'received_date' => (string) $row->received_date,
            'grn_number' => (string) $row->grn_number,
            'supplier' => trim((string) ($row->supplier_code ?? '').' '.(string) ($row->supplier_name ?? '')),
            'age_days' => max(0, (int) CarbonImmutable::parse((string) $row->received_date)->startOfDay()->diffInDays(now()->startOfDay())),
            'invoice_progress' => (string) $row->invoice_progress,
            'invoice_count' => (int) $row->invoice_count,
            'accepted_quantity' => $this->decimal($row->accepted_quantity),
            'invoiced_quantity' => $this->decimal($row->invoiced_quantity),
            'returned_quantity' => $this->decimal($row->returned_quantity),
            'return_count' => (int) $row->return_count,
            'exposure_status' => (string) $row->exposure_status,
        ];
        foreach ($money as $field) {
            $result[$field] = $this->decimal($row->{$field});
        }

        return $result;
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? self::ZERO));
    }
}

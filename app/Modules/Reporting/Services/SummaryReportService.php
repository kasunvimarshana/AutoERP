<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Services\FinanceStatementService;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Purchase\Enums\PurchaseReturnStatus;

final class SummaryReportService
{
    private const FINAL_INVOICE_STATUSES = [
        InvoiceStatus::Posted->value,
        InvoiceStatus::PartiallyPaid->value,
        InvoiceStatus::Paid->value,
    ];

    private const COST_OF_GOODS_SOLD_CATEGORY = 'COGS';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinanceStatementService $statements,
        private readonly ReportBrandingResolver $branding,
        private readonly SalesSettlementBreakdownService $salesSettlements,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
    ): array {
        $sales = $this->invoiceSummary(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
            InvoiceDirection::Outbound,
            false,
        );
        $purchases = $this->invoiceSummary(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
            InvoiceDirection::Inbound,
            false,
        );
        $salesReturns = $this->invoiceSummary(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
            InvoiceDirection::Outbound,
            true,
        );
        $purchaseReturns = $this->purchaseReturnSummary(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
        );
        $profitAndLoss = $this->statements->profitAndLoss(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
        );
        $costOfSales = $this->sumStatementRows(
            $profitAndLoss['rows'],
            self::COST_OF_GOODS_SOLD_CATEGORY,
        );
        $otherExpenses = $this->math->sub(
            (string) $profitAndLoss['total_expenses'],
            $costOfSales,
        );
        $branding = $this->branding->resolve($tenantId, $organizationUnitId);

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'currency_code' => (string) ($branding['currency_code'] ?? ''),
            'documents' => [
                'sales' => $sales,
                'purchases' => $purchases,
                'sales_returns' => $salesReturns,
                'purchase_returns' => $purchaseReturns,
            ],
            'sales_settlement' => $this->salesSettlements->run(
                $tenantId,
                $organizationUnitId,
                $dateFrom,
                $dateTo,
            ),
            'payments' => [
                'received' => $this->paymentSummary(
                    $tenantId,
                    $organizationUnitId,
                    $dateFrom,
                    $dateTo,
                    PaymentDirection::Inbound,
                ),
                'sent' => $this->paymentSummary(
                    $tenantId,
                    $organizationUnitId,
                    $dateFrom,
                    $dateTo,
                    PaymentDirection::Outbound,
                ),
            ],
            'performance' => [
                'total_income' => (string) $profitAndLoss['total_revenue'],
                'cost_of_sales' => $costOfSales,
                'other_expenses' => $otherExpenses,
                'total_expenses' => (string) $profitAndLoss['total_expenses'],
                'net_profit' => (string) $profitAndLoss['net_profit'],
            ],
            'capabilities' => [
                'sales_returns' => [
                    'available' => true,
                    'source' => 'Finalized outbound credit notes',
                ],
                'purchase_returns' => [
                    'available' => true,
                    'source' => 'Posted purchase returns',
                ],
                'payroll' => [
                    'available' => false,
                    'source' => null,
                    'message' => 'Payroll is not available because no payroll transaction or payroll accounting category exists yet.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function invoiceSummary(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
        InvoiceDirection $direction,
        bool $creditNotes,
    ): array {
        $query = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->where('direction', $direction->value)
            ->whereIn('status', self::FINAL_INVOICE_STATUSES)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereNull('deleted_at');

        $this->organizationScope($query, 'organization_unit_id', $organizationUnitId);

        $creditNotes
            ? $query->where('invoice_type', InvoiceType::Credit->value)
            : $query->whereNotIn('invoice_type', [
                InvoiceType::Credit->value,
                InvoiceType::Debit->value,
            ]);

        $summary = $query
            ->selectRaw(
                'COUNT(*) as document_count, '
                .'COALESCE(SUM(subtotal), 0) as subtotal, '
                .'COALESCE(SUM(discount_total), 0) as discount_total, '
                .'COALESCE(SUM(tax_total), 0) as tax_total, '
                .'COALESCE(SUM(charge_total), 0) as charge_total, '
                .'COALESCE(SUM(grand_total), 0) as grand_total, '
                .'COALESCE(SUM(paid_total), 0) as paid_total'
            )
            ->first();

        return [
            'document_count' => (int) ($summary->document_count ?? 0),
            'subtotal' => $this->decimal($summary->subtotal ?? 0),
            'discount_total' => $this->decimal($summary->discount_total ?? 0),
            'tax_total' => $this->decimal($summary->tax_total ?? 0),
            'charge_total' => $this->decimal($summary->charge_total ?? 0),
            'grand_total' => $this->decimal($summary->grand_total ?? 0),
            'paid_total' => $this->decimal($summary->paid_total ?? 0),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function purchaseReturnSummary(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
    ): array {
        $query = DB::table('purchase_returns')
            ->where('tenant_id', $tenantId)
            ->where('status', PurchaseReturnStatus::Posted->value)
            ->whereBetween('return_date', [$dateFrom, $dateTo])
            ->whereNull('deleted_at');

        $this->organizationScope($query, 'organization_unit_id', $organizationUnitId);

        $summary = $query
            ->selectRaw(
                'COUNT(*) as document_count, '
                .'COALESCE(SUM(subtotal), 0) as subtotal, '
                .'COALESCE(SUM(adjustment_return_total), 0) as adjustment_total, '
                .'COALESCE(SUM(grand_total), 0) as grand_total'
            )
            ->first();

        return [
            'document_count' => (int) ($summary->document_count ?? 0),
            'subtotal' => $this->decimal($summary->subtotal ?? 0),
            'adjustment_total' => $this->decimal($summary->adjustment_total ?? 0),
            'grand_total' => $this->decimal($summary->grand_total ?? 0),
        ];
    }

    /**
     * @return array{amount: string, transaction_count: int, methods: list<array<string, int|string>>}
     */
    private function paymentSummary(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
        PaymentDirection $direction,
    ): array {
        $query = DB::table('payment_lines as lines')
            ->join('payments', 'payments.id', '=', 'lines.payment_id')
            ->where('payments.tenant_id', $tenantId)
            ->where('lines.tenant_id', $tenantId)
            ->where('payments.direction', $direction->value)
            ->where('payments.document_status', PaymentDocumentStatus::Approved->value)
            ->where('payments.posting_status', PaymentPostingStatus::Posted->value)
            ->whereBetween('payments.payment_date', [$dateFrom, $dateTo])
            ->whereNull('payments.deleted_at');

        $this->organizationScope($query, 'payments.organization_unit_id', $organizationUnitId);
        $this->organizationScope($query, 'lines.organization_unit_id', $organizationUnitId);

        $totals = (clone $query)
            ->selectRaw('COUNT(DISTINCT payments.id) as transaction_count, COALESCE(SUM(lines.amount), 0) as amount')
            ->first();
        $methods = $query
            ->select([
                'lines.payment_method_type_snapshot as type',
                'lines.payment_method_name_snapshot as name',
            ])
            ->selectRaw('COUNT(DISTINCT payments.id) as transaction_count, COALESCE(SUM(lines.amount), 0) as amount')
            ->groupBy([
                'lines.payment_method_type_snapshot',
                'lines.payment_method_name_snapshot',
            ])
            ->orderByDesc('amount')
            ->get()
            ->map(fn (object $row): array => [
                'type' => (string) $row->type,
                'name' => (string) $row->name,
                'transaction_count' => (int) $row->transaction_count,
                'amount' => $this->decimal($row->amount),
            ])
            ->values()
            ->all();

        return [
            'amount' => $this->decimal($totals->amount ?? 0),
            'transaction_count' => (int) ($totals->transaction_count ?? 0),
            'methods' => $methods,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function sumStatementRows(array $rows, string $categoryCode): string
    {
        $total = '0';

        foreach ($rows as $row) {
            if (($row['account_category_code'] ?? null) === $categoryCode) {
                $total = $this->math->add($total, (string) ($row['amount'] ?? '0'));
            }
        }

        return $this->decimal($total);
    }

    private function organizationScope(Builder $query, string $column, ?int $organizationUnitId): void
    {
        $organizationUnitId === null
            ? $query->whereNull($column)
            : $query->where($column, $organizationUnitId);
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? '0'));
    }
}

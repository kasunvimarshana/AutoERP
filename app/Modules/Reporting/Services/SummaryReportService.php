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
        private readonly ExpenseReportService $expenses,
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
            'performance' => $this->performance($tenantId, $organizationUnitId, $dateFrom, $dateTo),
            'operating_expenses' => $this->expenses->overview(
                $tenantId,
                $organizationUnitId,
                $dateFrom,
                $dateTo,
            ),
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
     * Return the canonical ledger-backed profitability metrics shared by reports and dashboards.
     *
     * @return array{total_income:string,cost_of_sales:string,gross_profit:string,other_expenses:string,total_expenses:string,net_profit:string}
     */
    public function performance(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
    ): array {
        $profitAndLoss = $this->statements->profitAndLoss(
            $tenantId,
            $organizationUnitId,
            $dateFrom,
            $dateTo,
        );
        $totalIncome = (string) $profitAndLoss['total_revenue'];
        $costOfSales = $this->sumStatementRows(
            $profitAndLoss['rows'],
            self::COST_OF_GOODS_SOLD_CATEGORY,
        );
        $totalExpenses = (string) $profitAndLoss['total_expenses'];

        return [
            'total_income' => $totalIncome,
            'cost_of_sales' => $costOfSales,
            'gross_profit' => $this->math->sub($totalIncome, $costOfSales),
            'other_expenses' => $this->math->sub($totalExpenses, $costOfSales),
            'total_expenses' => $totalExpenses,
            'net_profit' => (string) $profitAndLoss['net_profit'],
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

        $summary = [
            'document_count' => 0,
            'subtotal' => '0.000000',
            'discount_total' => '0.000000',
            'tax_total' => '0.000000',
            'charge_total' => '0.000000',
            'grand_total' => '0.000000',
            'paid_total' => '0.000000',
        ];

        foreach ($query->get(['subtotal', 'discount_total', 'tax_total', 'charge_total', 'grand_total', 'paid_total', 'exchange_rate']) as $invoice) {
            $rate = (string) $invoice->exchange_rate;
            $summary['document_count']++;
            $summary['subtotal'] = $this->math->add($summary['subtotal'], $this->math->mul((string) $invoice->subtotal, $rate));
            $summary['discount_total'] = $this->math->add($summary['discount_total'], $this->math->mul((string) $invoice->discount_total, $rate));
            $summary['tax_total'] = $this->math->add($summary['tax_total'], $this->math->mul((string) $invoice->tax_total, $rate));
            $summary['charge_total'] = $this->math->add($summary['charge_total'], $this->math->mul((string) $invoice->charge_total, $rate));
            $summary['grand_total'] = $this->math->add($summary['grand_total'], $this->math->mul((string) $invoice->grand_total, $rate));
            $summary['paid_total'] = $this->math->add($summary['paid_total'], $this->math->mul((string) $invoice->paid_total, $rate));
        }

        return $summary;
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

        $rows = $query->get([
            'payments.id as payment_id',
            'payments.exchange_rate',
            'lines.amount',
            'lines.payment_method_type_snapshot as type',
            'lines.payment_method_name_snapshot as name',
        ]);

        $amount = '0.000000';
        $paymentIds = [];
        $methodTotals = [];
        foreach ($rows as $row) {
            $baseAmount = $this->math->mul((string) $row->amount, (string) $row->exchange_rate);
            $amount = $this->math->add($amount, $baseAmount);
            $paymentIds[(int) $row->payment_id] = true;

            $methodKey = (string) $row->type."\0".(string) $row->name;
            $methodTotals[$methodKey] ??= [
                'type' => (string) $row->type,
                'name' => (string) $row->name,
                'transaction_ids' => [],
                'amount' => '0.000000',
            ];
            $methodTotals[$methodKey]['transaction_ids'][(int) $row->payment_id] = true;
            $methodTotals[$methodKey]['amount'] = $this->math->add($methodTotals[$methodKey]['amount'], $baseAmount);
        }

        $methods = array_values(array_map(static fn (array $method): array => [
            'type' => $method['type'],
            'name' => $method['name'],
            'transaction_count' => count($method['transaction_ids']),
            'amount' => $method['amount'],
        ], $methodTotals));
        usort($methods, fn (array $left, array $right): int => $this->math->compare($right['amount'], $left['amount']));

        return [
            'amount' => $amount,
            'transaction_count' => count($paymentIds),
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

<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\Payment;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleService\Enums\VehicleServiceInvoiceSettlementMode;

final class PaymentMethodWiseSummaryReportService
{
    public const SOURCE_ALL = 'all';

    public const SOURCE_SELLING = 'selling';

    public const SOURCE_SERVICE = 'vehicle_service';

    private const ZERO = '0.000000';

    private const POSTED_SALE_STATUS = 'posted';

    private const ACTIVE_SERVICE_LINK_STATUS = 'active';

    private const FINAL_INVOICE_STATUSES = [
        InvoiceStatus::Posted->value,
        InvoiceStatus::PartiallyPaid->value,
        InvoiceStatus::Paid->value,
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly ReportBrandingResolver $branding,
    ) {}

    /** @return array<string, mixed> */
    public function run(int $tenantId, ?int $organizationUnitId, string $dateFrom, string $dateTo, string $source): array
    {
        $brand = $this->branding->resolve($tenantId, $organizationUnitId);
        $defaultCurrency = (string) ($brand['currency_code'] ?? '');
        $days = [];
        $methods = [];

        if ($source !== self::SOURCE_SERVICE) {
            $sales = DB::table('sales')
                ->join('invoices', 'invoices.id', '=', 'sales.invoice_id')
                ->where('sales.tenant_id', $tenantId)
                ->where('invoices.tenant_id', $tenantId)
                ->where('sales.status', self::POSTED_SALE_STATUS)
                ->whereNotNull('sales.due_date')
                ->where('invoices.invoice_type', InvoiceType::Sales->value)
                ->where('invoices.direction', InvoiceDirection::Outbound->value)
                ->whereIn('invoices.status', self::FINAL_INVOICE_STATUSES)
                ->whereNull('invoices.deleted_at')
                ->whereBetween('invoices.invoice_date', [$dateFrom, $dateTo]);
            $this->scope($sales, 'sales.organization_unit_id', $organizationUnitId);
            $this->scope($sales, 'invoices.organization_unit_id', $organizationUnitId);

            foreach ($sales->get(['invoices.invoice_date', 'invoices.currency_code_snapshot', 'invoices.grand_total']) as $sale) {
                $this->addCredit($days, (string) $sale->invoice_date, (string) ($sale->currency_code_snapshot ?: $defaultCurrency), (string) $sale->grand_total);
            }
        }

        if ($source !== self::SOURCE_SELLING) {
            $service = DB::table('vehicle_service_invoice_links as links')
                ->join('invoices', 'invoices.id', '=', 'links.invoice_id')
                ->where('links.tenant_id', $tenantId)
                ->where('invoices.tenant_id', $tenantId)
                ->where('links.status', self::ACTIVE_SERVICE_LINK_STATUS)
                ->where('links.settlement_mode', VehicleServiceInvoiceSettlementMode::Credit->value)
                ->where('invoices.invoice_type', InvoiceType::Service->value)
                ->where('invoices.direction', InvoiceDirection::Outbound->value)
                ->whereIn('invoices.status', self::FINAL_INVOICE_STATUSES)
                ->whereNull('invoices.deleted_at')
                ->whereBetween('invoices.invoice_date', [$dateFrom, $dateTo]);
            $this->scope($service, 'links.organization_unit_id', $organizationUnitId);
            $this->scope($service, 'invoices.organization_unit_id', $organizationUnitId);

            foreach ($service->get(['invoices.invoice_date', 'invoices.currency_code_snapshot', 'links.invoice_total']) as $invoice) {
                $this->addCredit($days, (string) $invoice->invoice_date, (string) ($invoice->currency_code_snapshot ?: $defaultCurrency), (string) $invoice->invoice_total);
            }
        }

        $receipts = DB::table('payment_allocations as allocations')
            ->join('payments', 'payments.id', '=', 'allocations.payment_id')
            ->join('payment_lines as lines', 'lines.payment_id', '=', 'payments.id')
            ->join('invoices', 'invoices.id', '=', 'allocations.invoice_id')
            ->where('allocations.tenant_id', $tenantId)
            ->where('payments.tenant_id', $tenantId)
            ->where('lines.tenant_id', $tenantId)
            ->where('invoices.tenant_id', $tenantId)
            ->where('allocations.status', AllocationStatus::Active->value)
            ->where('payments.direction', PaymentDirection::Inbound->value)
            ->where('payments.document_status', PaymentDocumentStatus::Approved->value)
            ->where('payments.posting_status', PaymentPostingStatus::Posted->value)
            ->whereNull('payments.deleted_at')
            ->whereIn('invoices.status', self::FINAL_INVOICE_STATUSES)
            ->whereNull('invoices.deleted_at')
            ->where('invoices.direction', InvoiceDirection::Outbound->value)
            ->whereIn('invoices.invoice_type', $source === self::SOURCE_ALL
                ? [InvoiceType::Sales->value, InvoiceType::Service->value]
                : [$source === self::SOURCE_SELLING ? InvoiceType::Sales->value : InvoiceType::Service->value])
            ->whereBetween('payments.payment_date', [$dateFrom, $dateTo])
            ->orderBy('allocations.id')
            ->orderBy('lines.line_number')
            ->select([
                'allocations.id as allocation_id', 'allocations.allocated_amount',
                'payments.payment_date', 'payments.total_amount',
                'invoices.currency_code_snapshot', 'lines.payment_method_code_snapshot',
                'lines.payment_method_name_snapshot', 'lines.amount as line_amount',
            ]);

        $receipts->where(function ($query) use ($source): void {
            if ($source !== self::SOURCE_SERVICE) {
                $query->orWhere(function ($selling): void {
                    $selling->where('invoices.invoice_type', InvoiceType::Sales->value)
                        ->whereExists(function ($sales): void {
                            $sales->selectRaw('1')
                                ->from('sales')
                                ->whereColumn('sales.invoice_id', 'invoices.id')
                                ->whereColumn('sales.tenant_id', 'invoices.tenant_id');
                        });
                });
            }
            if ($source !== self::SOURCE_SELLING) {
                $query->orWhere(function ($service): void {
                    $service->where('invoices.invoice_type', InvoiceType::Service->value)
                        ->whereExists(function ($links): void {
                            $links->selectRaw('1')
                                ->from('vehicle_service_invoice_links as service_links')
                                ->whereColumn('service_links.invoice_id', 'invoices.id')
                                ->whereColumn('service_links.tenant_id', 'invoices.tenant_id')
                                ->where('service_links.status', self::ACTIVE_SERVICE_LINK_STATUS);
                        });
                });
            }
        });

        $this->scope($receipts, 'allocations.organization_unit_id', $organizationUnitId);
        $this->scope($receipts, 'payments.organization_unit_id', $organizationUnitId);
        $this->scope($receipts, 'lines.organization_unit_id', $organizationUnitId);
        $this->scope($receipts, 'invoices.organization_unit_id', $organizationUnitId);

        $allocationRows = [];
        $allocationId = null;
        foreach ($receipts->cursor() as $row) {
            if ($allocationId !== null && $allocationId !== (int) $row->allocation_id) {
                $this->addAllocation($days, $methods, $allocationRows, $defaultCurrency);
                $allocationRows = [];
            }
            $allocationId = (int) $row->allocation_id;
            $allocationRows[] = $row;
        }
        if ($allocationRows !== []) {
            $this->addAllocation($days, $methods, $allocationRows, $defaultCurrency);
        }

        ksort($days);
        ksort($methods);
        $totals = [];
        foreach ($days as &$day) {
            $day['methods'] = array_replace(array_fill_keys(array_keys($methods), self::ZERO), $day['methods']);
            $currency = $day['currency'];
            $totals[$currency] ??= ['currency' => $currency, 'credit_sales' => self::ZERO, 'receipts' => self::ZERO, 'methods' => array_fill_keys(array_keys($methods), self::ZERO)];
            $totals[$currency]['credit_sales'] = $this->math->add($totals[$currency]['credit_sales'], $day['credit_sales']);
            $totals[$currency]['receipts'] = $this->math->add($totals[$currency]['receipts'], $day['receipts']);
            foreach ($day['methods'] as $key => $amount) {
                $totals[$currency]['methods'][$key] = $this->math->add($totals[$currency]['methods'][$key], $amount);
            }
        }
        unset($day);

        return [
            'period' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'source' => $source,
            'organization_unit' => (string) ($brand['organization_unit_name'] ?: $brand['tenant_name']),
            'methods' => array_map(fn (string $key, string $name): array => ['key' => $key, 'name' => $name], array_keys($methods), array_values($methods)),
            'days' => array_values($days),
            'totals' => array_values($totals),
        ];
    }

    /** @param array<string, mixed> $result */
    public function definition(array $result): ReportDefinition
    {
        $columns = [new ReportColumn('date', 'Date', format: 'date'), new ReportColumn('currency', 'Currency')];
        foreach ($result['methods'] as $index => $method) {
            $columns[] = new ReportColumn('method_'.$index, $method['name'], format: 'money');
        }
        $columns[] = new ReportColumn('receipts', 'Receipts total', format: 'money');
        $columns[] = new ReportColumn('credit_sales', 'New credit sales', format: 'money');

        return new ReportDefinition(
            key: 'payment-method-wise-summary',
            title: 'Payment Method Wise Summary',
            group: 'Invoice & Payment',
            model: Payment::class,
            columns: $columns,
            description: 'Daily allocated receipts and new credit sales for Selling and Vehicle Service.',
            orientation: 'landscape',
        );
    }

    /** @param array<string, mixed> $result @return list<array<string, string>> */
    public function exportRows(array $result): array
    {
        $exportRow = static function (array $day) use ($result): array {
            $row = ['date' => $day['date'] ?? 'Total', 'currency' => $day['currency'], 'receipts' => $day['receipts'], 'credit_sales' => $day['credit_sales']];
            foreach ($result['methods'] as $index => $method) {
                $row['method_'.$index] = $day['methods'][$method['key']];
            }

            return $row;
        };

        return array_merge(array_map($exportRow, $result['days']), array_map($exportRow, $result['totals']));
    }

    /** @param array<string, array<string, mixed>> $days */
    private function addCredit(array &$days, string $date, string $currency, string $amount): void
    {
        $day = &$this->day($days, $date, $currency);
        $day['credit_sales'] = $this->math->add($day['credit_sales'], $amount);
    }

    /** @param array<string, array<string, mixed>> $days @param array<string, string> $methods @param list<object> $rows */
    private function addAllocation(array &$days, array &$methods, array $rows, string $defaultCurrency): void
    {
        $first = $rows[0];
        $remaining = $this->math->normalize((string) $first->allocated_amount);
        $paymentTotal = $this->math->normalize((string) $first->total_amount);
        if ($this->math->compare($remaining, self::ZERO) <= 0 || $this->math->compare($paymentTotal, self::ZERO) <= 0) {
            return;
        }

        $day = &$this->day($days, (string) $first->payment_date, (string) ($first->currency_code_snapshot ?: $defaultCurrency));
        $day['receipts'] = $this->math->add($day['receipts'], $remaining);
        $lastIndex = count($rows) - 1;
        foreach ($rows as $index => $row) {
            $amount = $index === $lastIndex
                ? $remaining
                : $this->math->div($this->math->mul((string) $first->allocated_amount, (string) $row->line_amount, 12), $paymentTotal);
            if ($this->math->compare($amount, $remaining) > 0) {
                $amount = $remaining;
            }
            $key = (string) $row->payment_method_code_snapshot;
            $methods[$key] = (string) $row->payment_method_name_snapshot;
            $day['methods'][$key] = $this->math->add($day['methods'][$key] ?? self::ZERO, $amount);
            $remaining = $this->math->sub($remaining, $amount);
        }
    }

    /** @param array<string, array<string, mixed>> $days @return array<string, mixed> */
    private function &day(array &$days, string $date, string $currency): array
    {
        $key = $date.'|'.$currency;
        $days[$key] ??= ['date' => $date, 'currency' => $currency, 'credit_sales' => self::ZERO, 'receipts' => self::ZERO, 'methods' => []];

        return $days[$key];
    }

    private function scope(Builder $query, string $column, ?int $organizationUnitId): void
    {
        $organizationUnitId === null
            ? $query->whereNull($column)
            : $query->where($column, $organizationUnitId);
    }
}

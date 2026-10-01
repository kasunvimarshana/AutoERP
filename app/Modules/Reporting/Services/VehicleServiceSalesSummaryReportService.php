<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;

final class VehicleServiceSalesSummaryReportService
{
    private const SOURCE_TYPE = 'vehicle_service_job';

    private const SOURCE_LINE_TYPE = 'vehicle_service_job_line';

    private const ACTIVE_LINK_STATUS = 'active';

    private const ALLOCATION_SCALE = 12;

    private const ZERO = '0';

    private const FINAL_INVOICE_STATUSES = [
        InvoiceStatus::Posted->value,
        InvoiceStatus::PartiallyPaid->value,
        InvoiceStatus::Paid->value,
    ];

    public function __construct(private readonly DecimalMath $math) {}

    /** @param array<string, mixed> $params @return array<string, mixed> */
    public function run(array $params): array
    {
        $tenantId = (int) $params['tenant_id'];
        $organizationUnitId = isset($params['organization_unit_id'])
            ? (int) $params['organization_unit_id']
            : null;

        $sales = DB::table('invoice_source_lines as source_lines')
            ->join('invoices', function ($join): void {
                $join->on('invoices.id', '=', 'source_lines.invoice_id')
                    ->on('invoices.tenant_id', '=', 'source_lines.tenant_id');
            })
            ->join('vehicle_service_job_lines as job_lines', function ($join): void {
                $join->on('job_lines.id', '=', 'source_lines.source_line_id')
                    ->on('job_lines.tenant_id', '=', 'source_lines.tenant_id');
            })
            ->join('vehicle_service_jobs as jobs', function ($join): void {
                $join->on('jobs.id', '=', 'job_lines.vehicle_service_job_id')
                    ->on('jobs.tenant_id', '=', 'job_lines.tenant_id');
            })
            ->join('vehicle_service_invoice_links as invoice_links', function ($join): void {
                $join->on('invoice_links.invoice_id', '=', 'invoices.id')
                    ->on('invoice_links.vehicle_service_job_id', '=', 'jobs.id')
                    ->on('invoice_links.tenant_id', '=', 'source_lines.tenant_id');
            })
            ->join('items', function ($join): void {
                $join->on('items.id', '=', 'job_lines.item_id')
                    ->on('items.tenant_id', '=', 'job_lines.tenant_id');
            })
            ->where('source_lines.tenant_id', $tenantId)
            ->where('source_lines.source_type', self::SOURCE_TYPE)
            ->where('source_lines.source_line_type', self::SOURCE_LINE_TYPE)
            ->where('invoice_links.status', self::ACTIVE_LINK_STATUS)
            ->where('job_lines.line_source_type', '<>', VehicleServiceLineSourceType::ComboChild->value)
            ->where('job_lines.is_billable', true)
            ->where('invoices.direction', InvoiceDirection::Outbound->value)
            ->whereIn('invoices.status', self::FINAL_INVOICE_STATUSES)
            ->whereNull('invoices.deleted_at')
            ->whereNull('jobs.deleted_at')
            ->when(
                $organizationUnitId === null,
                fn ($query) => $query->whereNull('jobs.organization_unit_id'),
                fn ($query) => $query->where('jobs.organization_unit_id', $organizationUnitId),
            )
            ->when(
                ! empty($params['item_id']),
                fn ($query) => $query->where('job_lines.item_id', (int) $params['item_id']),
            )
            ->selectRaw('invoices.id as invoice_id, invoices.invoice_date, invoice_links.invoice_total, invoice_links.source_line_total, jobs.id as job_id, job_lines.item_id, items.code as item_code, items.name as item_name, SUM(source_lines.invoiced_quantity) as quantity, SUM(source_lines.invoiced_line_total) as sales_amount')
            ->groupBy('invoices.id', 'invoices.invoice_date', 'invoice_links.invoice_total', 'invoice_links.source_line_total', 'jobs.id', 'job_lines.item_id', 'items.code', 'items.name')
            ->get();

        $invoiceIds = $sales->pluck('invoice_id')->unique()->all();
        $payments = collect();

        if ($invoiceIds !== []) {
            $payments = DB::table('vehicle_service_payment_links as links')
                ->join('payments', function ($join): void {
                    $join->on('payments.id', '=', 'links.payment_id')
                        ->on('payments.tenant_id', '=', 'links.tenant_id');
                })
                ->where('links.tenant_id', $tenantId)
                ->where('links.status', self::ACTIVE_LINK_STATUS)
                ->whereIn('links.invoice_id', $invoiceIds)
                ->whereNull('payments.deleted_at')
                ->whereNull('payments.reversed_at')
                ->where('payments.document_status', PaymentDocumentStatus::Approved->value)
                ->where('payments.posting_status', PaymentPostingStatus::Posted->value)
                ->where('payments.direction', PaymentDirection::Inbound->value)
                ->when(
                    $organizationUnitId === null,
                    fn ($query) => $query->whereNull('links.organization_unit_id'),
                    fn ($query) => $query->where('links.organization_unit_id', $organizationUnitId),
                )
                ->when(
                    ! empty($params['date_from']),
                    fn ($query) => $query->whereDate('payments.payment_date', '>=', $params['date_from']),
                )
                ->when(
                    ! empty($params['date_to']),
                    fn ($query) => $query->whereDate('payments.payment_date', '<=', $params['date_to']),
                )
                ->selectRaw('links.invoice_id, SUM(links.allocated_amount) as collected_amount')
                ->groupBy('links.invoice_id')
                ->get()
                ->keyBy('invoice_id');
        }

        $items = $sales->groupBy('item_id')->map(function ($invoiceRows) use ($payments, $params): ?array {
            $salesAmount = $this->math->normalize(self::ZERO);
            $quantity = $this->math->normalize(self::ZERO);
            $collectedAmount = $this->math->normalize(self::ZERO);
            $jobIds = [];

            foreach ($invoiceRows as $invoiceRow) {
                $sourceAmount = (string) $invoiceRow->sales_amount;
                $sourceLineTotal = (string) $invoiceRow->source_line_total;
                $amount = $this->math->compare($sourceLineTotal, self::ZERO) > 0
                    ? $this->math->mul(
                        $sourceAmount,
                        $this->math->div(
                            (string) $invoiceRow->invoice_total,
                            $sourceLineTotal,
                            self::ALLOCATION_SCALE,
                        ),
                    )
                    : $sourceAmount;
                $inSalesPeriod = (empty($params['date_from']) || $invoiceRow->invoice_date >= $params['date_from'])
                    && (empty($params['date_to']) || $invoiceRow->invoice_date <= $params['date_to']);

                if ($inSalesPeriod) {
                    $salesAmount = $this->math->add($salesAmount, $amount);
                    $quantity = $this->math->add($quantity, (string) $invoiceRow->quantity);
                    $jobIds[(int) $invoiceRow->job_id] = true;
                }

                $invoiceTotal = (string) $invoiceRow->invoice_total;
                $invoicePaid = (string) ($payments->get($invoiceRow->invoice_id)->collected_amount ?? self::ZERO);
                if ($this->math->compare($invoiceTotal, self::ZERO) > 0) {
                    $collectedAmount = $this->math->add(
                        $collectedAmount,
                        $this->math->mul(
                            $invoicePaid,
                            $this->math->div($amount, $invoiceTotal, self::ALLOCATION_SCALE),
                        ),
                    );
                }
            }

            $item = $invoiceRows->first();

            if ($this->math->isZero($salesAmount) && $this->math->isZero($collectedAmount)) {
                return null;
            }

            return [
                'item' => [
                    'id' => (int) $item->item_id,
                    'code' => $item->item_code,
                    'name' => $item->item_name,
                ],
                'quantity' => $quantity,
                'job_count' => count($jobIds),
                'sales_amount' => $salesAmount,
                'collected_amount' => $collectedAmount,
            ];
        })->filter()->values()->sortByDesc('sales_amount')->values();

        return [
            'data' => $items,
            'summary' => [
                'item_count' => $items->count(),
                'quantity' => $this->math->sum($items->pluck('quantity')),
                'job_count' => $sales
                    ->filter(fn (object $row): bool => (empty($params['date_from']) || $row->invoice_date >= $params['date_from'])
                        && (empty($params['date_to']) || $row->invoice_date <= $params['date_to']))
                    ->pluck('job_id')
                    ->unique()
                    ->count(),
                'sales_amount' => $this->math->sum($items->pluck('sales_amount')),
                'collected_amount' => $this->math->sum($items->pluck('collected_amount')),
            ],
            'date_basis' => ['sales' => 'invoice_date', 'collected' => 'payment_date'],
        ];
    }
}

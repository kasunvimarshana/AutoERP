<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\VehicleService\Constants\VehicleServiceFinanceSource;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
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
            ->leftJoin('items', function ($join): void {
                $join->on('items.id', '=', 'job_lines.item_id')
                    ->on('items.tenant_id', '=', 'job_lines.tenant_id');
            })
            ->leftJoin('customers', function ($join): void {
                $join->on('customers.id', '=', 'jobs.customer_id')
                    ->on('customers.tenant_id', '=', 'jobs.tenant_id');
            })
            ->leftJoin('vehicles', function ($join): void {
                $join->on('vehicles.id', '=', 'jobs.vehicle_id')
                    ->on('vehicles.tenant_id', '=', 'jobs.tenant_id');
            })
            ->where('source_lines.tenant_id', $tenantId)
            ->where('source_lines.source_type', self::SOURCE_TYPE)
            ->where('source_lines.source_line_type', self::SOURCE_LINE_TYPE)
            ->where('invoice_links.status', self::ACTIVE_LINK_STATUS)
            ->where('job_lines.line_source_type', '<>', VehicleServiceLineSourceType::ComboChild->value)
            ->where('job_lines.is_billable', true)
            ->where('jobs.status', '<>', VehicleServiceJobStatus::Cancelled->value)
            ->where('invoices.direction', InvoiceDirection::Outbound->value)
            ->whereIn('invoices.status', self::FINAL_INVOICE_STATUSES)
            ->whereNull('invoices.deleted_at')
            ->whereNull('jobs.deleted_at')
            ->when(! empty($params['date_from']), fn ($query) => $query->whereDate('invoices.invoice_date', '>=', $params['date_from']))
            ->when(! empty($params['date_to']), fn ($query) => $query->whereDate('invoices.invoice_date', '<=', $params['date_to']))
            ->when(
                $organizationUnitId === null,
                fn ($query) => $query->whereNull('jobs.organization_unit_id'),
                fn ($query) => $query->where('jobs.organization_unit_id', $organizationUnitId),
            )
            ->when(
                ! empty($params['item_id']),
                fn ($query) => $query->whereExists(function ($subquery) use ($params): void {
                    $subquery->selectRaw('1')
                        ->from('vehicle_service_job_lines as filter_lines')
                        ->whereColumn('filter_lines.vehicle_service_job_id', 'jobs.id')
                        ->whereColumn('filter_lines.tenant_id', 'jobs.tenant_id')
                        ->where('filter_lines.item_id', (int) $params['item_id']);
                }),
            )
            ->selectRaw(
                'invoices.id as invoice_id, invoices.invoice_date, invoice_links.invoice_total, invoice_links.source_line_total, '
                .'jobs.id as job_id, jobs.job_number, jobs.job_date, jobs.grand_total, jobs.supervisor_commission_amount, '
                .'COALESCE(customers.display_name, customers.name) as customer_name, '
                .'COALESCE(vehicles.registration_number, vehicles.vehicle_number) as vehicle_name, '
                .'job_lines.id as job_line_id, job_lines.parent_line_id, job_lines.line_source_type, job_lines.quantity as line_quantity, '
                .'job_lines.item_id, job_lines.description, items.code as item_code, items.name as item_name, items.is_stockable, '
                .'SUM(source_lines.invoiced_quantity) as quantity, SUM(source_lines.invoiced_line_total) as sales_amount',
            )
            ->groupBy(
                'invoices.id', 'invoices.invoice_date', 'invoice_links.invoice_total', 'invoice_links.source_line_total',
                'jobs.id', 'jobs.job_number', 'jobs.job_date', 'jobs.grand_total', 'jobs.supervisor_commission_amount',
                'customers.display_name', 'customers.name', 'vehicles.registration_number', 'vehicles.vehicle_number',
                'job_lines.id', 'job_lines.parent_line_id', 'job_lines.line_source_type', 'job_lines.quantity',
                'job_lines.item_id', 'job_lines.description', 'items.code', 'items.name', 'items.is_stockable',
            )
            ->get();

        $invoiceIds = $sales->pluck('invoice_id')->unique()->all();
        $payments = $this->paymentTotals($invoiceIds, $tenantId, $organizationUnitId, $params);
        $jobs = [];
        $lineQuantities = [];
        $lineBaseQuantities = [];

        foreach ($sales as $row) {
            $invoiceSales = $this->allocatedInvoiceSales($row);
            $inSalesPeriod = (empty($params['date_from']) || $row->invoice_date >= $params['date_from'])
                && (empty($params['date_to']) || $row->invoice_date <= $params['date_to']);

            $invoiceTotal = (string) $row->invoice_total;
            $invoicePaid = (string) ($payments->get($row->invoice_id)->collected_amount ?? self::ZERO);
            $collected = $this->math->compare($invoiceTotal, self::ZERO) > 0
                ? $this->math->mul($invoicePaid, $this->math->div($invoiceSales, $invoiceTotal, self::ALLOCATION_SCALE))
                : self::ZERO;

            if ($inSalesPeriod) {
                $jobId = (int) $row->job_id;
                $lineId = (int) $row->job_line_id;
                if (! isset($jobs[$jobId])) {
                    $jobs[$jobId] = $this->emptyJob($row);
                }
                $jobs[$jobId]['revenue'] = $this->math->add($jobs[$jobId]['revenue'], $invoiceSales);
                $jobs[$jobId]['collected'] = $this->math->add($jobs[$jobId]['collected'], $collected);
                $lineQuantities[$lineId] = $this->math->add($lineQuantities[$lineId] ?? self::ZERO, (string) $row->quantity);
                $lineBaseQuantities[$lineId] = (string) $row->line_quantity;
                $this->addSalesLine($jobs[$jobId], $row, $invoiceSales, (string) $row->quantity);
            }
        }

        if ($jobs === []) {
            return $this->result([]);
        }

        $jobIds = array_keys($jobs);
        $lines = DB::table('vehicle_service_job_lines as lines')
            ->leftJoin('items', function ($join): void {
                $join->on('items.id', '=', 'lines.item_id')->on('items.tenant_id', '=', 'lines.tenant_id');
            })
            ->leftJoin('inventory_movements as movements', function ($join): void {
                $join->on('movements.id', '=', 'lines.inventory_movement_id')
                    ->on('movements.tenant_id', '=', 'lines.tenant_id')
                    ->on('movements.source_id', '=', 'lines.vehicle_service_job_id')
                    ->on('movements.source_line_id', '=', 'lines.id')
                    ->where('movements.source_type', VehicleServiceFinanceSource::JOB)
                    ->where('movements.source_line_type', VehicleServiceFinanceSource::JOB_LINE);
            })
            ->where('lines.tenant_id', $tenantId)
            ->whereIn('lines.vehicle_service_job_id', $jobIds)
            ->where('lines.status', '<>', 'cancelled')
            ->selectRaw(
                'lines.id, lines.vehicle_service_job_id as job_id, lines.parent_line_id, lines.line_number, lines.line_source_type, '
                .'lines.quantity, lines.unit_cost, lines.unit_price, lines.inventory_movement_id, '
                .'lines.item_id, lines.description, items.code as item_code, items.name as item_name, items.is_stockable, '
                .'movements.total_cost as movement_cost, movements.status as movement_status',
            )
            ->orderBy('lines.line_number')
            ->get();

        $assignments = DB::table('vehicle_service_line_employees')
            ->where('tenant_id', $tenantId)
            ->whereIn('vehicle_service_job_id', $jobIds)
            ->selectRaw('vehicle_service_job_id as job_id, vehicle_service_job_line_id as job_line_id, SUM(commission_amount) as commission_amount')
            ->groupBy('vehicle_service_job_id', 'vehicle_service_job_line_id')
            ->get()
            ->keyBy('job_line_id');

        foreach ($lines as $line) {
            $jobId = (int) $line->job_id;
            $lineId = (int) $line->id;
            $sourceType = (string) $line->line_source_type;
            $parentId = $line->parent_line_id === null ? null : (int) $line->parent_line_id;
            $fraction = $this->invoicedFraction($line, $parentId, $lineQuantities, $lineBaseQuantities);
            $quantity = $this->math->mul((string) $line->quantity, $fraction);
            $cost = $this->lineCost($line, $sourceType);
            $cost = $this->math->mul($cost, $fraction);
            $commission = $this->math->mul(
                (string) ($assignments->get($lineId)->commission_amount ?? self::ZERO),
                $fraction,
            );

            if ($fraction === self::ZERO || $this->math->isZero($fraction)) {
                continue;
            }

            if ($sourceType === VehicleServiceLineSourceType::ComboParent->value) {
                continue;
            }

            $category = $sourceType === VehicleServiceLineSourceType::ComboChild->value
                ? 'combo'
                : ((bool) $line->is_stockable ? 'stock' : 'other');
            $bucket = &$jobs[$jobId];
            $bucket['direct_cost'] = $this->math->add($bucket['direct_cost'], $cost);
            $bucket['commission'] = $this->math->add($bucket['commission'], $commission);

            if ($category === 'stock') {
                $bucket['stock_cost'] = $this->math->add($bucket['stock_cost'], $cost);
                $bucket['stock_quantity'] = $this->math->add($bucket['stock_quantity'], $quantity);
                $this->addCostToItem($bucket['stock_items'], $line, $quantity, $cost);
            } elseif ($category === 'combo') {
                $bucket['combo_component_cost'] = $this->math->add($bucket['combo_component_cost'], $cost);
                if ((bool) $line->is_stockable) {
                    $bucket['combo_stock_quantity'] = $this->math->add($bucket['combo_stock_quantity'], $quantity);
                    $bucket['combo_stock_cost'] = $this->math->add($bucket['combo_stock_cost'], $cost);
                }
                $this->addComboComponent($bucket['combos'], $parentId, $line, $quantity, $cost);
            } else {
                $bucket['other_cost'] = $this->math->add($bucket['other_cost'], $cost);
                $this->addCostToItem($bucket['other_items'], $line, $quantity, $cost);
            }

            unset($bucket);
        }

        foreach ($jobs as &$job) {
            $job['used_stock_cost'] = $this->math->add($job['stock_cost'], $job['combo_stock_cost']);
            $jobRevenue = $job['revenue'];
            $jobGrandTotal = $job['grand_total'];
            $supervisorCommission = $this->math->compare($jobGrandTotal, self::ZERO) > 0
                ? $this->math->mul($job['supervisor_commission'], $this->math->div($jobRevenue, $jobGrandTotal))
                : self::ZERO;
            $job['commission'] = $this->math->add($job['commission'], $supervisorCommission);
            foreach (['stock', 'combo', 'other'] as $category) {
                $revenue = $job['categories'][$category]['revenue'];
                $job['categories'][$category]['cost'] = match ($category) {
                    'stock' => $job['stock_cost'],
                    'combo' => $job['combo_component_cost'],
                    default => $job['other_cost'],
                };
                $job['categories'][$category]['profit'] = $this->math->sub($revenue, $job['categories'][$category]['cost']);
            }
            $job['profit'] = $this->math->sub($this->math->sub($job['revenue'], $job['direct_cost']), $job['commission']);
            $job['margin'] = $this->math->compare($job['revenue'], self::ZERO) > 0
                ? $this->math->mul($this->math->div($job['profit'], $job['revenue']), '100')
                : self::ZERO;
            unset(
                $job['grand_total'],
                $job['supervisor_commission'],
                $job['combo_stock_cost'],
                $job['combo_stock_quantity'],
                $job['stock_quantity'],
                $job['combo_component_cost'],
                $job['other_cost'],
            );
        }
        unset($job);

        return $this->result(array_values($jobs));
    }

    /** @param list<int> $invoiceIds @param array<string, mixed> $params */
    private function paymentTotals(array $invoiceIds, int $tenantId, ?int $organizationUnitId, array $params): Collection
    {
        if ($invoiceIds === []) {
            return collect();
        }

        return DB::table('vehicle_service_payment_links as links')
            ->join('payments', function ($join): void {
                $join->on('payments.id', '=', 'links.payment_id')->on('payments.tenant_id', '=', 'links.tenant_id');
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
            ->when(! empty($params['date_from']), fn ($query) => $query->whereDate('payments.payment_date', '>=', $params['date_from']))
            ->when(! empty($params['date_to']), fn ($query) => $query->whereDate('payments.payment_date', '<=', $params['date_to']))
            ->selectRaw('links.invoice_id, SUM(links.allocated_amount) as collected_amount')
            ->groupBy('links.invoice_id')
            ->get()
            ->keyBy('invoice_id');
    }

    private function allocatedInvoiceSales(object $row): string
    {
        $sourceAmount = (string) $row->sales_amount;
        $sourceLineTotal = (string) $row->source_line_total;

        return $this->math->compare($sourceLineTotal, self::ZERO) > 0
            ? $this->math->mul(
                $sourceAmount,
                $this->math->div((string) $row->invoice_total, $sourceLineTotal, self::ALLOCATION_SCALE),
            )
            : $sourceAmount;
    }

    /** @return array<string, mixed> */
    private function emptyJob(object $row): array
    {
        return [
            'id' => (int) $row->job_id,
            'job_number' => (string) $row->job_number,
            'date' => (string) $row->job_date,
            'customer' => ['name' => (string) ($row->customer_name ?? 'Walk-in customer')],
            'vehicle' => ['name' => (string) ($row->vehicle_name ?? '—')],
            'revenue' => self::ZERO,
            'collected' => self::ZERO,
            'direct_cost' => self::ZERO,
            'stock_cost' => self::ZERO,
            'used_stock_cost' => self::ZERO,
            'combo_component_cost' => self::ZERO,
            'combo_stock_cost' => self::ZERO,
            'other_cost' => self::ZERO,
            'stock_quantity' => self::ZERO,
            'combo_stock_quantity' => self::ZERO,
            'commission' => self::ZERO,
            'profit' => self::ZERO,
            'margin' => self::ZERO,
            'grand_total' => (string) $row->grand_total,
            'supervisor_commission' => (string) $row->supervisor_commission_amount,
            'stock_items' => [],
            'combos' => [],
            'other_items' => [],
            'categories' => [
                'stock' => ['item_count' => 0, 'quantity' => self::ZERO, 'revenue' => self::ZERO, 'cost' => self::ZERO, 'profit' => self::ZERO],
                'combo' => ['revenue' => self::ZERO, 'cost' => self::ZERO, 'profit' => self::ZERO],
                'other' => ['revenue' => self::ZERO, 'cost' => self::ZERO, 'profit' => self::ZERO],
            ],
        ];
    }

    /** @param array<string, mixed> $job */
    private function addSalesLine(array &$job, object $row, string $amount, string $quantity): void
    {
        $sourceType = (string) $row->line_source_type;
        $category = $sourceType === VehicleServiceLineSourceType::ComboParent->value
            ? 'combo'
            : ((bool) $row->is_stockable ? 'stock' : 'other');
        $job['categories'][$category]['revenue'] = $this->math->add($job['categories'][$category]['revenue'], $amount);
        $item = [
            'id' => $row->item_id === null ? null : (int) $row->item_id,
            'code' => (string) $row->item_code,
            'name' => (string) ($row->item_name ?? $row->description),
        ];

        if ($category === 'combo') {
            $key = (string) $row->job_line_id;
            if (! isset($job['combos'][$key])) {
                $job['combos'][$key] = [
                    'line_id' => (int) $row->job_line_id,
                    'item' => $item,
                    'quantity' => self::ZERO,
                    'sales_amount' => self::ZERO,
                    'component_cost' => self::ZERO,
                    'stock_cost' => self::ZERO,
                    'stock_quantity' => self::ZERO,
                    'components' => [],
                ];
            }
            $job['combos'][$key]['quantity'] = $this->math->add($job['combos'][$key]['quantity'], $quantity);
            $job['combos'][$key]['sales_amount'] = $this->math->add($job['combos'][$key]['sales_amount'], $amount);

            return;
        }

        $rows = $category === 'stock' ? 'stock_items' : 'other_items';
        $key = (string) $row->job_line_id;
        if (! isset($job[$rows][$key])) {
            $job[$rows][$key] = ['line_id' => (int) $row->job_line_id, 'item' => $item, 'quantity' => self::ZERO, 'sales_amount' => self::ZERO, 'cost' => self::ZERO];
        }
        $job[$rows][$key]['quantity'] = $this->math->add($job[$rows][$key]['quantity'], $quantity);
        $job[$rows][$key]['sales_amount'] = $this->math->add($job[$rows][$key]['sales_amount'], $amount);
    }

    private function invoicedFraction(object $line, ?int $parentId, array $lineQuantities, array $lineBaseQuantities): string
    {
        $salesLineId = $parentId ?? (int) $line->id;
        $invoicedQuantity = $lineQuantities[$salesLineId] ?? self::ZERO;
        $baseQuantity = $parentId === null ? (string) $line->quantity : (string) ($lineBaseQuantities[$parentId] ?? self::ZERO);

        if ($this->math->compare($baseQuantity, self::ZERO) <= 0) {
            return self::ZERO;
        }

        return $this->math->div($invoicedQuantity, $baseQuantity, self::ALLOCATION_SCALE);
    }

    private function lineCost(object $line, string $sourceType): string
    {
        if ($sourceType === VehicleServiceLineSourceType::ComboParent->value) {
            return self::ZERO;
        }

        if ((bool) $line->is_stockable && $line->movement_cost !== null && $line->movement_status === 'posted') {
            return (string) $line->movement_cost;
        }

        return $this->math->mul((string) $line->quantity, (string) $line->unit_cost);
    }

    /** @param array<string, mixed> $rows */
    private function addCostToItem(array &$rows, object $line, string $quantity, string $cost): void
    {
        $key = (string) $line->id;
        if (! isset($rows[$key])) {
            $rows[$key] = [
                'line_id' => (int) $line->id,
                'item' => ['id' => $line->item_id === null ? null : (int) $line->item_id, 'code' => (string) ($line->item_code ?? ''), 'name' => (string) ($line->item_name ?? $line->description)],
                'quantity' => self::ZERO,
                'sales_amount' => self::ZERO,
                'cost' => self::ZERO,
            ];
        }
        $rows[$key]['quantity'] = $this->math->add($rows[$key]['quantity'], $quantity);
        $rows[$key]['cost'] = $this->math->add($rows[$key]['cost'], $cost);
    }

    /** @param array<string, mixed> $combos */
    private function addComboComponent(array &$combos, ?int $parentId, object $line, string $quantity, string $cost): void
    {
        if ($parentId === null || ! isset($combos[(string) $parentId])) {
            return;
        }

        $combo = &$combos[(string) $parentId];
        $combo['component_cost'] = $this->math->add($combo['component_cost'], $cost);
        if ((bool) $line->is_stockable) {
            $combo['stock_cost'] = $this->math->add($combo['stock_cost'], $cost);
            $combo['stock_quantity'] = $this->math->add($combo['stock_quantity'], $quantity);
        }
        $key = (string) $line->id;
        if (! isset($combo['components'][$key])) {
            $combo['components'][$key] = [
                'item' => ['id' => $line->item_id === null ? null : (int) $line->item_id, 'code' => (string) ($line->item_code ?? ''), 'name' => (string) ($line->item_name ?? $line->description)],
                'kind' => (bool) $line->is_stockable ? 'stock' : 'labour_or_service',
                'quantity' => self::ZERO,
                'cost' => self::ZERO,
            ];
        }
        $combo['components'][$key]['quantity'] = $this->math->add($combo['components'][$key]['quantity'], $quantity);
        $combo['components'][$key]['cost'] = $this->math->add($combo['components'][$key]['cost'], $cost);
        unset($combo);
    }

    /** @param list<array<string, mixed>> $jobs @return array<string, mixed> */
    private function result(array $jobs): array
    {
        $summary = [
            'job_count' => count($jobs),
            'revenue' => self::ZERO,
            'collected' => self::ZERO,
            'direct_cost' => self::ZERO,
            'stock_cost' => self::ZERO,
            'commission' => self::ZERO,
            'profit' => self::ZERO,
            'margin' => self::ZERO,
            'stock' => ['item_count' => 0, 'quantity' => self::ZERO, 'revenue' => self::ZERO, 'cost' => self::ZERO, 'profit' => self::ZERO, 'items' => []],
            'combo' => ['combo_count' => 0, 'item_count' => 0, 'quantity' => self::ZERO, 'component_stock_quantity' => self::ZERO, 'revenue' => self::ZERO, 'stock_cost' => self::ZERO, 'component_cost' => self::ZERO, 'profit' => self::ZERO, 'items' => []],
            'other_service_revenue' => self::ZERO,
        ];
        $stockItems = [];
        $comboItems = [];

        foreach ($jobs as &$job) {
            foreach (['stock_items', 'combos', 'other_items'] as $key) {
                $job[$key] = array_values($job[$key]);
            }
            foreach ($job['combos'] as &$combo) {
                $combo['components'] = array_values($combo['components']);
                $combo['profit'] = $this->math->sub($combo['sales_amount'], $combo['component_cost']);
                $summary['combo']['combo_count']++;
                $summary['combo']['quantity'] = $this->math->add($summary['combo']['quantity'], $combo['quantity']);
                $summary['combo']['revenue'] = $this->math->add($summary['combo']['revenue'], $combo['sales_amount']);
                $summary['combo']['stock_cost'] = $this->math->add($summary['combo']['stock_cost'], $combo['stock_cost']);
                $summary['combo']['component_stock_quantity'] = $this->math->add($summary['combo']['component_stock_quantity'], $combo['stock_quantity']);
                $summary['combo']['component_cost'] = $this->math->add($summary['combo']['component_cost'], $combo['component_cost']);
                $summary['combo']['profit'] = $this->math->add($summary['combo']['profit'], $combo['profit']);
                $this->accumulateRankedItem(
                    $comboItems,
                    $combo['item'],
                    $combo['quantity'],
                    $combo['sales_amount'],
                    $combo['component_cost'],
                    (int) $job['id'],
                    $combo['stock_cost'],
                );
                foreach ($combo['components'] as $component) {
                    $this->accumulateComboComponent($comboItems, $combo['item'], $component);
                }
            }
            unset($combo);
            foreach ($job['stock_items'] as $item) {
                $summary['stock']['quantity'] = $this->math->add($summary['stock']['quantity'], $item['quantity']);
                $summary['stock']['revenue'] = $this->math->add($summary['stock']['revenue'], $item['sales_amount']);
                $summary['stock']['cost'] = $this->math->add($summary['stock']['cost'], $item['cost']);
                $this->accumulateRankedItem(
                    $stockItems,
                    $item['item'],
                    $item['quantity'],
                    $item['sales_amount'],
                    $item['cost'],
                    (int) $job['id'],
                );
            }
            $summary['stock']['profit'] = $this->math->sub($summary['stock']['revenue'], $summary['stock']['cost']);
            $summary['stock']['item_count'] = count($stockItems);
            $summary['revenue'] = $this->math->add($summary['revenue'], $job['revenue']);
            $summary['collected'] = $this->math->add($summary['collected'], $job['collected']);
            $summary['direct_cost'] = $this->math->add($summary['direct_cost'], $job['direct_cost']);
            $summary['stock_cost'] = $this->math->add($summary['stock_cost'], $job['used_stock_cost']);
            $summary['commission'] = $this->math->add($summary['commission'], $job['commission']);
            $summary['profit'] = $this->math->add($summary['profit'], $job['profit']);
            $summary['other_service_revenue'] = $this->math->add($summary['other_service_revenue'], $job['categories']['other']['revenue']);
            unset($job['categories']);
        }
        unset($job);

        $summary['stock']['items'] = $this->finalizeRankedItems($stockItems);
        $summary['stock']['item_count'] = count($summary['stock']['items']);
        $summary['combo']['items'] = $this->finalizeRankedItems($comboItems, true);
        $summary['combo']['item_count'] = count($summary['combo']['items']);
        $summary['margin'] = $this->math->compare($summary['revenue'], self::ZERO) > 0
            ? $this->math->mul($this->math->div($summary['profit'], $summary['revenue']), '100')
            : self::ZERO;

        usort($jobs, static fn (array $left, array $right): int => strcmp($right['date'], $left['date']) ?: strcmp($right['job_number'], $left['job_number']));

        return [
            'jobs' => $jobs,
            'summary' => $summary,
            'date_basis' => ['sales' => 'invoice_date', 'collected' => 'payment_date'],
        ];
    }

    /** @param array<string, mixed> $items @param array{id: int|null, code: string, name: string} $item */
    private function accumulateRankedItem(
        array &$items,
        array $item,
        string $quantity,
        string $sales,
        string $cost,
        int $jobId,
        ?string $stockCost = null,
    ): void {
        $key = $this->itemKey($item);
        if (! isset($items[$key])) {
            $items[$key] = [
                'item' => $item,
                'quantity' => self::ZERO,
                'sales_amount' => self::ZERO,
                'cost' => self::ZERO,
                'stock_cost' => self::ZERO,
                'job_ids' => [],
                'components' => [],
            ];
        }
        $items[$key]['quantity'] = $this->math->add($items[$key]['quantity'], $quantity);
        $items[$key]['sales_amount'] = $this->math->add($items[$key]['sales_amount'], $sales);
        $items[$key]['cost'] = $this->math->add($items[$key]['cost'], $cost);
        $items[$key]['stock_cost'] = $this->math->add($items[$key]['stock_cost'], $stockCost ?? self::ZERO);
        $items[$key]['job_ids'][$jobId] = true;
    }

    /** @param array<string, mixed> $items @param array{id: int|null, code: string, name: string} $comboItem @param array<string, mixed> $component */
    private function accumulateComboComponent(array &$items, array $comboItem, array $component): void
    {
        $comboKey = $this->itemKey($comboItem);
        $componentKey = $this->itemKey($component['item']).':'.$component['kind'];
        if (! isset($items[$comboKey]['components'][$componentKey])) {
            $items[$comboKey]['components'][$componentKey] = [
                'item' => $component['item'],
                'kind' => $component['kind'],
                'quantity' => self::ZERO,
                'cost' => self::ZERO,
            ];
        }
        $items[$comboKey]['components'][$componentKey]['quantity'] = $this->math->add(
            $items[$comboKey]['components'][$componentKey]['quantity'],
            (string) $component['quantity'],
        );
        $items[$comboKey]['components'][$componentKey]['cost'] = $this->math->add(
            $items[$comboKey]['components'][$componentKey]['cost'],
            (string) $component['cost'],
        );
    }

    /** @param array{id: int|null, code: string, name: string} $item */
    private function itemKey(array $item): string
    {
        if ($item['id'] !== null) {
            return 'id:'.$item['id'];
        }

        return 'code:'.$item['code'];
    }

    /** @param array<string, mixed> $items @return list<array<string, mixed>> */
    private function finalizeRankedItems(array $items, bool $includeComponents = false): array
    {
        foreach ($items as &$item) {
            $item['job_count'] = count($item['job_ids']);
            $item['profit'] = $this->math->sub($item['sales_amount'], $item['cost']);
            $item['margin'] = $this->math->compare($item['sales_amount'], self::ZERO) > 0
                ? $this->math->mul($this->math->div($item['profit'], $item['sales_amount']), '100')
                : self::ZERO;
            if ($includeComponents) {
                $item['components'] = array_values($item['components']);
            } else {
                unset($item['stock_cost'], $item['components']);
            }
            unset($item['job_ids']);
        }
        unset($item);

        $items = array_values($items);
        usort($items, function (array $left, array $right): int {
            $quantityOrder = $this->math->compare((string) $right['quantity'], (string) $left['quantity']);
            if ($quantityOrder !== 0) {
                return $quantityOrder;
            }

            $salesOrder = $this->math->compare((string) $right['sales_amount'], (string) $left['sales_amount']);

            return $salesOrder !== 0 ? $salesOrder : strcmp((string) $left['item']['name'], (string) $right['item']['name']);
        });

        return $items;
    }
}

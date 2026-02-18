<?php

namespace App\Modules\Reporting\Services;

use App\Modules\Reporting\DTOs\CreateReportDTO;
use App\Modules\Reporting\DTOs\ExecuteReportDTO;
use App\Modules\Reporting\Enums\ReportExecutionStatus;
use App\Modules\Reporting\Events\ReportExecuted;
use App\Modules\Reporting\Events\ReportFailed;
use App\Modules\Reporting\Models\Report;
use App\Modules\Reporting\Models\ReportExecution;
use App\Modules\Reporting\Repositories\ReportRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

/**
 * Report Service
 *
 * Handles report generation, execution, and management.
 */
class ReportService extends BaseService
{
    public function __construct(
        protected ReportRepository $reportRepository,
    ) {}

    /**
     * Create a new report
     */
    public function createReport(CreateReportDTO $dto): Report
    {
        $data = $dto->toArray();
        $data['tenant_id'] = auth()->user()->tenant_id;

        return $this->reportRepository->create($data);
    }

    /**
     * Update a report
     */
    public function updateReport(string|int $reportId, array $data): bool
    {
        return $this->reportRepository->update($reportId, $data);
    }

    /**
     * Delete a report
     */
    public function deleteReport(string|int $reportId): bool
    {
        return $this->reportRepository->delete($reportId);
    }

    /**
     * Execute a report
     *
     * @throws \Throwable
     */
    public function executeReport(ExecuteReportDTO $dto): array
    {
        $report = $this->reportRepository->findOrFail($dto->reportId);

        $startTime = microtime(true);

        // Create execution record
        $execution = ReportExecution::create([
            'tenant_id' => auth()->user()->tenant_id,
            'report_id' => $dto->reportId,
            'executed_by_id' => $dto->executedById ?? auth()->id(),
            'parameters' => $dto->parameters,
            'status' => ReportExecutionStatus::RUNNING,
        ]);

        try {
            // Execute the report query
            $results = $this->runReportQuery($report, $dto);

            $executionTime = microtime(true) - $startTime;

            // Update execution record
            $execution->update([
                'status' => ReportExecutionStatus::COMPLETED,
                'execution_time' => $executionTime,
                'result_count' => count($results),
            ]);

            event(new ReportExecuted($execution, $results));

            $this->logInfo('Report executed successfully', [
                'report_id' => $dto->reportId,
                'execution_time' => $executionTime,
                'result_count' => count($results),
            ]);

            return [
                'execution' => $execution,
                'results' => $results,
            ];
        } catch (\Exception $e) {
            $execution->update([
                'status' => ReportExecutionStatus::FAILED,
                'error_message' => $e->getMessage(),
                'execution_time' => microtime(true) - $startTime,
            ]);

            event(new ReportFailed($execution, $e->getMessage()));

            $this->logError('Report execution failed', [
                'report_id' => $dto->reportId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Run report query based on configuration
     */
    protected function runReportQuery(Report $report, ExecuteReportDTO $dto): array
    {
        $config = $report->query_config;

        // Check if it's a pre-built report
        if (isset($config['pre_built']) && $config['pre_built']) {
            return $this->executePreBuiltReport(
                $config['report_name'],
                $dto->parameters,
                $dto->startDate,
                $dto->endDate
            );
        }

        // Otherwise, execute custom query
        return $this->executeCustomQuery($config, $dto->parameters);
    }

    /**
     * Execute pre-built report
     */
    protected function executePreBuiltReport(
        string $reportName,
        array $parameters,
        ?string $startDate,
        ?string $endDate
    ): array {
        return match ($reportName) {
            'stock_level' => $this->getStockLevelReport($parameters),
            'stock_movement' => $this->getStockMovementReport($startDate, $endDate, $parameters),
            'low_stock_alert' => $this->getLowStockAlertReport(),
            'stock_valuation' => $this->getStockValuationReport($parameters),
            'expiry_report' => $this->getExpiryReport($parameters),
            'sales_summary' => $this->getSalesSummaryReport($startDate, $endDate, $parameters),
            'top_products' => $this->getTopProductsReport($startDate, $endDate, $parameters),
            'customer_sales' => $this->getCustomerSalesReport($startDate, $endDate, $parameters),
            'sales_trend' => $this->getSalesTrendReport($startDate, $endDate),
            'invoice_aging' => $this->getInvoiceAgingReport(),
            'purchase_summary' => $this->getPurchaseSummaryReport($startDate, $endDate, $parameters),
            'supplier_performance' => $this->getSupplierPerformanceReport($startDate, $endDate),
            'purchase_order_status' => $this->getPurchaseOrderStatusReport($parameters),
            'production_summary' => $this->getProductionSummaryReport($startDate, $endDate, $parameters),
            'production_efficiency' => $this->getProductionEfficiencyReport($startDate, $endDate),
            'material_consumption' => $this->getMaterialConsumptionReport($startDate, $endDate, $parameters),
            'work_order_status' => $this->getWorkOrderStatusReport($parameters),
            'cash_flow' => $this->getCashFlowReport($startDate, $endDate),
            'accounts_receivable_aging' => $this->getAccountsReceivableAgingReport(),
            'accounts_payable_aging' => $this->getAccountsPayableAgingReport(),
            default => throw new \InvalidArgumentException("Unknown report: {$reportName}"),
        };
    }

    /**
     * Execute custom query
     */
    protected function executeCustomQuery(array $config, array $parameters): array
    {
        $query = DB::table($config['table'] ?? 'products');

        // Apply filters
        if (isset($config['filters'])) {
            foreach ($config['filters'] as $filter) {
                $value = $parameters[$filter['field']] ?? $filter['value'] ?? null;
                if ($value !== null) {
                    $query->where($filter['field'], $filter['operator'] ?? '=', $value);
                }
            }
        }

        // Apply grouping
        if (isset($config['group_by'])) {
            $query->groupBy($config['group_by']);
        }

        // Apply ordering
        if (isset($config['order_by'])) {
            $direction = $config['order_direction'] ?? 'asc';
            $query->orderBy($config['order_by'], $direction);
        }

        // Apply limit
        if (isset($config['limit'])) {
            $query->limit($config['limit']);
        }

        // Select columns
        if (isset($config['columns'])) {
            $query->select($config['columns']);
        }

        return $query->get()->toArray();
    }

    // ==================== PRE-BUILT REPORTS ====================

    /**
     * Stock Level Report
     */
    protected function getStockLevelReport(array $parameters): array
    {
        $query = DB::table('products as p')
            ->leftJoin('stock_ledgers as sl', 'p.id', '=', 'sl.product_id')
            ->select([
                'p.id',
                'p.sku',
                'p.name',
                DB::raw('COALESCE(SUM(sl.quantity), 0) as current_stock'),
                'p.reorder_level',
                'p.min_stock_level',
                'p.max_stock_level',
            ])
            ->where('p.track_inventory', true)
            ->groupBy('p.id', 'p.sku', 'p.name', 'p.reorder_level', 'p.min_stock_level', 'p.max_stock_level');

        if (isset($parameters['warehouse_id'])) {
            $query->where('sl.warehouse_id', $parameters['warehouse_id']);
        }

        if (isset($parameters['product_id'])) {
            $query->where('p.id', $parameters['product_id']);
        }

        return $query->get()->toArray();
    }

    /**
     * Stock Movement Report
     */
    protected function getStockMovementReport(?string $startDate, ?string $endDate, array $parameters): array
    {
        $query = DB::table('stock_ledgers as sl')
            ->join('products as p', 'sl.product_id', '=', 'p.id')
            ->select([
                'sl.id',
                'sl.transaction_date',
                'p.sku',
                'p.name as product_name',
                'sl.movement_type',
                'sl.quantity',
                'sl.unit_cost',
                'sl.total_cost',
                'sl.reference_type',
                'sl.reference_id',
                'sl.notes',
            ]);

        if ($startDate && $endDate) {
            $query->whereBetween('sl.transaction_date', [$startDate, $endDate]);
        }

        if (isset($parameters['product_id'])) {
            $query->where('sl.product_id', $parameters['product_id']);
        }

        if (isset($parameters['warehouse_id'])) {
            $query->where('sl.warehouse_id', $parameters['warehouse_id']);
        }

        if (isset($parameters['movement_type'])) {
            $query->where('sl.movement_type', $parameters['movement_type']);
        }

        return $query->orderBy('sl.transaction_date', 'desc')->get()->toArray();
    }

    /**
     * Low Stock Alert Report
     */
    protected function getLowStockAlertReport(): array
    {
        return DB::table('products as p')
            ->leftJoin('stock_ledgers as sl', 'p.id', '=', 'sl.product_id')
            ->select([
                'p.id',
                'p.sku',
                'p.name',
                DB::raw('COALESCE(SUM(sl.quantity), 0) as current_stock'),
                'p.reorder_level',
                'p.min_stock_level',
            ])
            ->where('p.track_inventory', true)
            ->whereNotNull('p.reorder_level')
            ->groupBy('p.id', 'p.sku', 'p.name', 'p.reorder_level', 'p.min_stock_level')
            ->havingRaw('COALESCE(SUM(sl.quantity), 0) <= p.reorder_level')
            ->get()
            ->toArray();
    }

    /**
     * Stock Valuation Report
     */
    protected function getStockValuationReport(array $parameters): array
    {
        $query = DB::table('products as p')
            ->leftJoin('stock_ledgers as sl', 'p.id', '=', 'sl.product_id')
            ->select([
                'p.id',
                'p.sku',
                'p.name',
                'p.buying_price',
                DB::raw('COALESCE(SUM(sl.quantity), 0) as current_stock'),
                DB::raw('COALESCE(SUM(sl.quantity), 0) * p.buying_price as stock_value'),
            ])
            ->where('p.track_inventory', true)
            ->groupBy('p.id', 'p.sku', 'p.name', 'p.buying_price');

        if (isset($parameters['warehouse_id'])) {
            $query->where('sl.warehouse_id', $parameters['warehouse_id']);
        }

        return $query->get()->toArray();
    }

    /**
     * Expiry Report
     */
    protected function getExpiryReport(array $parameters): array
    {
        $days = $parameters['days'] ?? 30;
        $expiryDate = date('Y-m-d', strtotime("+{$days} days"));

        return DB::table('stock_ledgers as sl')
            ->join('products as p', 'sl.product_id', '=', 'p.id')
            ->select([
                'p.id',
                'p.sku',
                'p.name as product_name',
                'sl.batch_number',
                'sl.lot_number',
                'sl.expiry_date',
                'sl.quantity',
                'sl.warehouse_id',
            ])
            ->where('p.track_expiry', true)
            ->whereNotNull('sl.expiry_date')
            ->where('sl.expiry_date', '<=', $expiryDate)
            ->where('sl.quantity', '>', 0)
            ->orderBy('sl.expiry_date')
            ->get()
            ->toArray();
    }

    /**
     * Sales Summary Report
     */
    protected function getSalesSummaryReport(?string $startDate, ?string $endDate, array $parameters): array
    {
        $query = DB::table('sales_orders as so')
            ->leftJoin('sales_order_items as soi', 'so.id', '=', 'soi.sales_order_id')
            ->leftJoin('products as p', 'soi.product_id', '=', 'p.id')
            ->leftJoin('customers as c', 'so.customer_id', '=', 'c.id')
            ->select([
                'so.id',
                'so.order_number',
                'so.order_date',
                'c.name as customer_name',
                'so.total_amount',
                'so.status',
                DB::raw('COUNT(soi.id) as total_items'),
            ])
            ->groupBy('so.id', 'so.order_number', 'so.order_date', 'c.name', 'so.total_amount', 'so.status');

        if ($startDate && $endDate) {
            $query->whereBetween('so.order_date', [$startDate, $endDate]);
        }

        if (isset($parameters['customer_id'])) {
            $query->where('so.customer_id', $parameters['customer_id']);
        }

        if (isset($parameters['status'])) {
            $query->where('so.status', $parameters['status']);
        }

        return $query->orderBy('so.order_date', 'desc')->get()->toArray();
    }

    /**
     * Top Products Report
     */
    protected function getTopProductsReport(?string $startDate, ?string $endDate, array $parameters): array
    {
        $limit = $parameters['limit'] ?? 10;

        $query = DB::table('sales_order_items as soi')
            ->join('sales_orders as so', 'soi.sales_order_id', '=', 'so.id')
            ->join('products as p', 'soi.product_id', '=', 'p.id')
            ->select([
                'p.id',
                'p.sku',
                'p.name as product_name',
                DB::raw('SUM(soi.quantity) as total_quantity_sold'),
                DB::raw('SUM(soi.quantity * soi.unit_price) as total_revenue'),
                DB::raw('COUNT(DISTINCT so.id) as order_count'),
            ])
            ->groupBy('p.id', 'p.sku', 'p.name');

        if ($startDate && $endDate) {
            $query->whereBetween('so.order_date', [$startDate, $endDate]);
        }

        return $query->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Customer Sales Report
     */
    protected function getCustomerSalesReport(?string $startDate, ?string $endDate, array $parameters): array
    {
        $query = DB::table('sales_orders as so')
            ->join('customers as c', 'so.customer_id', '=', 'c.id')
            ->select([
                'c.id',
                'c.name as customer_name',
                'c.email',
                'c.phone',
                DB::raw('COUNT(so.id) as total_orders'),
                DB::raw('SUM(so.total_amount) as total_sales'),
                DB::raw('AVG(so.total_amount) as average_order_value'),
            ])
            ->groupBy('c.id', 'c.name', 'c.email', 'c.phone');

        if ($startDate && $endDate) {
            $query->whereBetween('so.order_date', [$startDate, $endDate]);
        }

        if (isset($parameters['customer_id'])) {
            $query->where('c.id', $parameters['customer_id']);
        }

        return $query->orderBy('total_sales', 'desc')->get()->toArray();
    }

    /**
     * Sales Trend Report
     */
    protected function getSalesTrendReport(?string $startDate, ?string $endDate): array
    {
        $query = DB::table('sales_orders')
            ->select([
                DB::raw('DATE(order_date) as date'),
                DB::raw('COUNT(id) as order_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('AVG(total_amount) as average_order_value'),
            ])
            ->groupBy(DB::raw('DATE(order_date)'));

        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }

        return $query->orderBy('date')->get()->toArray();
    }

    /**
     * Invoice Aging Report
     */
    protected function getInvoiceAgingReport(): array
    {
        return DB::table('invoices')
            ->join('customers as c', 'invoices.customer_id', '=', 'c.id')
            ->select([
                'invoices.id',
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.due_date',
                'c.name as customer_name',
                'invoices.total_amount',
                'invoices.paid_amount',
                DB::raw('invoices.total_amount - invoices.paid_amount as balance'),
                DB::raw('DATEDIFF(CURDATE(), invoices.due_date) as days_overdue'),
            ])
            ->whereIn('invoices.status', ['pending', 'partially_paid'])
            ->orderBy('days_overdue', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Purchase Summary Report
     */
    protected function getPurchaseSummaryReport(?string $startDate, ?string $endDate, array $parameters): array
    {
        $query = DB::table('purchase_orders as po')
            ->join('suppliers as s', 'po.supplier_id', '=', 's.id')
            ->select([
                'po.id',
                'po.order_number',
                'po.order_date',
                's.name as supplier_name',
                'po.total_amount',
                'po.status',
            ]);

        if ($startDate && $endDate) {
            $query->whereBetween('po.order_date', [$startDate, $endDate]);
        }

        if (isset($parameters['supplier_id'])) {
            $query->where('po.supplier_id', $parameters['supplier_id']);
        }

        if (isset($parameters['status'])) {
            $query->where('po.status', $parameters['status']);
        }

        return $query->orderBy('po.order_date', 'desc')->get()->toArray();
    }

    /**
     * Supplier Performance Report
     */
    protected function getSupplierPerformanceReport(?string $startDate, ?string $endDate): array
    {
        $query = DB::table('purchase_orders as po')
            ->join('suppliers as s', 'po.supplier_id', '=', 's.id')
            ->select([
                's.id',
                's.name as supplier_name',
                DB::raw('COUNT(po.id) as total_orders'),
                DB::raw('SUM(po.total_amount) as total_purchase_value'),
                DB::raw('AVG(po.total_amount) as average_order_value'),
                DB::raw('SUM(CASE WHEN po.status = "completed" THEN 1 ELSE 0 END) as completed_orders'),
                DB::raw('SUM(CASE WHEN po.status = "cancelled" THEN 1 ELSE 0 END) as cancelled_orders'),
            ])
            ->groupBy('s.id', 's.name');

        if ($startDate && $endDate) {
            $query->whereBetween('po.order_date', [$startDate, $endDate]);
        }

        return $query->orderBy('total_purchase_value', 'desc')->get()->toArray();
    }

    /**
     * Purchase Order Status Report
     */
    protected function getPurchaseOrderStatusReport(array $parameters): array
    {
        $query = DB::table('purchase_orders')
            ->join('suppliers as s', 'purchase_orders.supplier_id', '=', 's.id')
            ->select([
                'purchase_orders.id',
                'purchase_orders.order_number',
                'purchase_orders.order_date',
                'purchase_orders.expected_delivery_date',
                's.name as supplier_name',
                'purchase_orders.total_amount',
                'purchase_orders.status',
            ]);

        if (isset($parameters['status'])) {
            $query->where('purchase_orders.status', $parameters['status']);
        }

        return $query->orderBy('purchase_orders.order_date', 'desc')->get()->toArray();
    }

    /**
     * Production Summary Report
     */
    protected function getProductionSummaryReport(?string $startDate, ?string $endDate, array $parameters): array
    {
        $query = DB::table('production_orders as po')
            ->join('products as p', 'po.product_id', '=', 'p.id')
            ->select([
                'po.id',
                'po.order_number',
                'po.start_date',
                'po.end_date',
                'p.name as product_name',
                'po.quantity',
                'po.status',
            ]);

        if ($startDate && $endDate) {
            $query->whereBetween('po.start_date', [$startDate, $endDate]);
        }

        if (isset($parameters['product_id'])) {
            $query->where('po.product_id', $parameters['product_id']);
        }

        if (isset($parameters['status'])) {
            $query->where('po.status', $parameters['status']);
        }

        return $query->orderBy('po.start_date', 'desc')->get()->toArray();
    }

    /**
     * Production Efficiency Report
     */
    protected function getProductionEfficiencyReport(?string $startDate, ?string $endDate): array
    {
        $query = DB::table('production_orders as po')
            ->join('products as p', 'po.product_id', '=', 'p.id')
            ->select([
                'p.id',
                'p.name as product_name',
                DB::raw('COUNT(po.id) as total_orders'),
                DB::raw('SUM(po.quantity) as total_quantity_produced'),
                DB::raw('SUM(CASE WHEN po.status = "completed" THEN 1 ELSE 0 END) as completed_orders'),
                DB::raw('AVG(DATEDIFF(po.end_date, po.start_date)) as average_production_time'),
            ])
            ->groupBy('p.id', 'p.name');

        if ($startDate && $endDate) {
            $query->whereBetween('po.start_date', [$startDate, $endDate]);
        }

        return $query->get()->toArray();
    }

    /**
     * Material Consumption Report
     */
    protected function getMaterialConsumptionReport(?string $startDate, ?string $endDate, array $parameters): array
    {
        $query = DB::table('stock_ledgers as sl')
            ->join('products as p', 'sl.product_id', '=', 'p.id')
            ->select([
                'p.id',
                'p.sku',
                'p.name as product_name',
                DB::raw('SUM(ABS(sl.quantity)) as total_consumed'),
                DB::raw('SUM(ABS(sl.total_cost)) as total_cost'),
            ])
            ->where('sl.movement_type', 'production')
            ->groupBy('p.id', 'p.sku', 'p.name');

        if ($startDate && $endDate) {
            $query->whereBetween('sl.transaction_date', [$startDate, $endDate]);
        }

        if (isset($parameters['product_id'])) {
            $query->where('sl.product_id', $parameters['product_id']);
        }

        return $query->get()->toArray();
    }

    /**
     * Work Order Status Report
     */
    protected function getWorkOrderStatusReport(array $parameters): array
    {
        $query = DB::table('work_orders as wo')
            ->join('production_orders as po', 'wo.production_order_id', '=', 'po.id')
            ->join('products as p', 'po.product_id', '=', 'p.id')
            ->select([
                'wo.id',
                'wo.order_number',
                'wo.start_date',
                'wo.end_date',
                'p.name as product_name',
                'wo.quantity',
                'wo.status',
            ]);

        if (isset($parameters['status'])) {
            $query->where('wo.status', $parameters['status']);
        }

        return $query->orderBy('wo.start_date', 'desc')->get()->toArray();
    }

    /**
     * Cash Flow Report
     */
    protected function getCashFlowReport(?string $startDate, ?string $endDate): array
    {
        $inflows = DB::table('journal_entries as je')
            ->join('journal_entry_lines as jel', 'je.id', '=', 'jel.journal_entry_id')
            ->join('accounts as a', 'jel.account_id', '=', 'a.id')
            ->select([
                DB::raw('DATE(je.entry_date) as date'),
                DB::raw('SUM(jel.credit) as cash_inflow'),
            ])
            ->where('a.account_type', 'asset')
            ->where('a.name', 'like', '%cash%')
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$startDate ?? '1900-01-01', $endDate ?? '2100-12-31'])
            ->groupBy(DB::raw('DATE(je.entry_date)'));

        $outflows = DB::table('journal_entries as je')
            ->join('journal_entry_lines as jel', 'je.id', '=', 'jel.journal_entry_id')
            ->join('accounts as a', 'jel.account_id', '=', 'a.id')
            ->select([
                DB::raw('DATE(je.entry_date) as date'),
                DB::raw('SUM(jel.debit) as cash_outflow'),
            ])
            ->where('a.account_type', 'asset')
            ->where('a.name', 'like', '%cash%')
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$startDate ?? '1900-01-01', $endDate ?? '2100-12-31'])
            ->groupBy(DB::raw('DATE(je.entry_date)'));

        // Merge inflows and outflows
        $inflowsData = $inflows->get()->keyBy('date');
        $outflowsData = $outflows->get()->keyBy('date');

        $allDates = $inflowsData->keys()->merge($outflowsData->keys())->unique()->sort();

        $result = [];
        foreach ($allDates as $date) {
            $inflow = $inflowsData->get($date)->cash_inflow ?? 0;
            $outflow = $outflowsData->get($date)->cash_outflow ?? 0;

            $result[] = [
                'date' => $date,
                'cash_inflow' => $inflow,
                'cash_outflow' => $outflow,
                'net_cash_flow' => $inflow - $outflow,
            ];
        }

        return $result;
    }

    /**
     * Accounts Receivable Aging Report
     */
    protected function getAccountsReceivableAgingReport(): array
    {
        return DB::table('invoices')
            ->join('customers as c', 'invoices.customer_id', '=', 'c.id')
            ->select([
                'c.id',
                'c.name as customer_name',
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.due_date) <= 30 THEN invoices.total_amount - invoices.paid_amount ELSE 0 END) as current'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.due_date) BETWEEN 31 AND 60 THEN invoices.total_amount - invoices.paid_amount ELSE 0 END) as days_31_60'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.due_date) BETWEEN 61 AND 90 THEN invoices.total_amount - invoices.paid_amount ELSE 0 END) as days_61_90'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.due_date) > 90 THEN invoices.total_amount - invoices.paid_amount ELSE 0 END) as over_90_days'),
                DB::raw('SUM(invoices.total_amount - invoices.paid_amount) as total_due'),
            ])
            ->whereIn('invoices.status', ['pending', 'partially_paid'])
            ->groupBy('c.id', 'c.name')
            ->get()
            ->toArray();
    }

    /**
     * Accounts Payable Aging Report
     */
    protected function getAccountsPayableAgingReport(): array
    {
        return DB::table('purchase_orders as po')
            ->join('suppliers as s', 'po.supplier_id', '=', 's.id')
            ->select([
                's.id',
                's.name as supplier_name',
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), po.expected_delivery_date) <= 30 THEN po.total_amount ELSE 0 END) as current'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), po.expected_delivery_date) BETWEEN 31 AND 60 THEN po.total_amount ELSE 0 END) as days_31_60'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), po.expected_delivery_date) BETWEEN 61 AND 90 THEN po.total_amount ELSE 0 END) as days_61_90'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), po.expected_delivery_date) > 90 THEN po.total_amount ELSE 0 END) as over_90_days'),
                DB::raw('SUM(po.total_amount) as total_payable'),
            ])
            ->where('po.status', 'pending')
            ->groupBy('s.id', 's.name')
            ->get()
            ->toArray();
    }

    /**
     * Get report by ID
     */
    public function getReport(string|int $reportId): Report
    {
        return $this->reportRepository->findOrFail($reportId);
    }

    /**
     * Get all reports
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllReports()
    {
        return $this->reportRepository->all();
    }

    /**
     * Get reports by module
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReportsByModule(string $module)
    {
        return $this->reportRepository->getByModule($module);
    }
}

<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateReports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        private readonly int $tenantId,
        private readonly int $userId,
        private readonly string $reportType,
        private readonly array $parameters
    ) {}

    public function handle(): void
    {
        Log::info("Starting report generation", [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'report_type' => $this->reportType,
        ]);

        $reportData = match ($this->reportType) {
            'sales' => $this->generateSalesReport(),
            'inventory' => $this->generateInventoryReport(),
            'financial' => $this->generateFinancialReport(),
            'customer' => $this->generateCustomerReport(),
            'procurement' => $this->generateProcurementReport(),
            default => throw new \InvalidArgumentException("Unknown report type: {$this->reportType}"),
        };

        // Generate report file
        $fileName = $this->generateReportFile($reportData);

        // Store report metadata
        DB::table('reports')->insert([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'report_type' => $this->reportType,
            'file_name' => $fileName,
            'parameters' => json_encode($this->parameters),
            'status' => 'completed',
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info("Report generation completed", [
            'report_type' => $this->reportType,
            'file_name' => $fileName,
        ]);

        // Notify user
        $user = \App\Models\User::find($this->userId);
        if ($user) {
            $user->notify(new \App\Notifications\ReportGeneratedNotification($this->reportType, $fileName));
        }
    }

    private function generateSalesReport(): array
    {
        $startDate = $this->parameters['start_date'] ?? now()->subMonth();
        $endDate = $this->parameters['end_date'] ?? now();

        return [
            'period' => "{$startDate} to {$endDate}",
            'total_orders' => DB::table('sales_orders')
                ->where('tenant_id', $this->tenantId)
                ->whereBetween('order_date', [$startDate, $endDate])
                ->count(),
            'total_revenue' => DB::table('sales_orders')
                ->where('tenant_id', $this->tenantId)
                ->whereBetween('order_date', [$startDate, $endDate])
                ->sum('total_amount'),
            'orders_by_status' => DB::table('sales_orders')
                ->where('tenant_id', $this->tenantId)
                ->whereBetween('order_date', [$startDate, $endDate])
                ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
                ->groupBy('status')
                ->get(),
        ];
    }

    private function generateInventoryReport(): array
    {
        return [
            'total_products' => DB::table('products')
                ->where('tenant_id', $this->tenantId)
                ->count(),
            'total_stock_value' => DB::table('stock_ledgers')
                ->where('tenant_id', $this->tenantId)
                ->sum(DB::raw('quantity * unit_cost')),
            'low_stock_items' => DB::table('products')
                ->where('tenant_id', $this->tenantId)
                ->whereColumn('current_stock', '<=', 'reorder_level')
                ->count(),
            'stock_by_location' => DB::table('stock_ledgers')
                ->where('tenant_id', $this->tenantId)
                ->select('location_id', DB::raw('SUM(quantity) as total_quantity'))
                ->groupBy('location_id')
                ->get(),
        ];
    }

    private function generateFinancialReport(): array
    {
        $startDate = $this->parameters['start_date'] ?? now()->subMonth();
        $endDate = $this->parameters['end_date'] ?? now();

        return [
            'period' => "{$startDate} to {$endDate}",
            'total_revenue' => DB::table('invoices')
                ->where('tenant_id', $this->tenantId)
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->where('status', 'paid')
                ->sum('total_amount'),
            'total_expenses' => DB::table('purchase_orders')
                ->where('tenant_id', $this->tenantId)
                ->whereBetween('order_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->sum('total_amount'),
            'outstanding_receivables' => DB::table('invoices')
                ->where('tenant_id', $this->tenantId)
                ->whereIn('status', ['pending', 'partial'])
                ->sum('balance_due'),
            'outstanding_payables' => DB::table('purchase_orders')
                ->where('tenant_id', $this->tenantId)
                ->whereIn('status', ['approved', 'partial'])
                ->sum('balance_due'),
        ];
    }

    private function generateCustomerReport(): array
    {
        return [
            'total_customers' => DB::table('customers')
                ->where('tenant_id', $this->tenantId)
                ->count(),
            'active_customers' => DB::table('customers')
                ->where('tenant_id', $this->tenantId)
                ->where('status', 'active')
                ->count(),
            'top_customers' => DB::table('customers')
                ->where('tenant_id', $this->tenantId)
                ->orderBy('total_spent', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    private function generateProcurementReport(): array
    {
        $startDate = $this->parameters['start_date'] ?? now()->subMonth();
        $endDate = $this->parameters['end_date'] ?? now();

        return [
            'period' => "{$startDate} to {$endDate}",
            'total_purchase_orders' => DB::table('purchase_orders')
                ->where('tenant_id', $this->tenantId)
                ->whereBetween('order_date', [$startDate, $endDate])
                ->count(),
            'total_purchase_amount' => DB::table('purchase_orders')
                ->where('tenant_id', $this->tenantId)
                ->whereBetween('order_date', [$startDate, $endDate])
                ->sum('total_amount'),
            'orders_by_vendor' => DB::table('purchase_orders')
                ->where('tenant_id', $this->tenantId)
                ->whereBetween('order_date', [$startDate, $endDate])
                ->select('vendor_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
                ->groupBy('vendor_id')
                ->get(),
        ];
    }

    private function generateReportFile(array $data): string
    {
        $fileName = sprintf(
            'reports/%s/%s_%s_%s.json',
            $this->tenantId,
            $this->reportType,
            now()->format('Y-m-d_His'),
            uniqid()
        );

        Storage::put($fileName, json_encode($data, JSON_PRETTY_PRINT));

        return $fileName;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Report generation failed", [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'report_type' => $this->reportType,
            'error' => $exception->getMessage(),
        ]);

        DB::table('reports')->insert([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'report_type' => $this->reportType,
            'status' => 'failed',
            'parameters' => json_encode($this->parameters),
            'error_message' => $exception->getMessage(),
            'failed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

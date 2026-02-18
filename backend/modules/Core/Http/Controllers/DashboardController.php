<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Modules\Core\Http\Requests\DashboardRequest;

/**
 * Dashboard Controller
 * 
 * Provides aggregated statistics and metrics for dashboard views.
 * All queries are tenant-scoped automatically via global scopes.
 */
class DashboardController extends BaseController
{
    /**
     * Get dashboard statistics
     * 
     * Returns key metrics including:
     * - Total customers count
     * - Total orders count
     * - Revenue metrics
     * - Active projects/tasks count
     * - Recent activity feed
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'dashboard_stats_' . auth()->user()->tenant_id;
        
        // Cache for 5 minutes to reduce database load
        $stats = Cache::remember($cacheKey, 300, function () {
            return $this->aggregateStats();
        });
        
        return $this->success($stats, 'Dashboard data retrieved successfully');
    }
    
    /**
     * Get recent activity feed
     * 
     * @param DashboardRequest $request
     * @return JsonResponse
     */
    public function activity(DashboardRequest $request): JsonResponse
    {
        $limit = $request->getLimit();
        
        // Get recent audit logs as activity feed
        $activities = DB::table('audit_logs')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->select('id', 'action', 'entity_type', 'entity_id', 'user_id', 'created_at')
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $this->formatActivityDescription($activity),
                    'time' => Carbon::parse($activity->created_at)->diffForHumans(),
                    'created_at' => $activity->created_at,
                ];
            });
        
        return $this->success($activities, 'Activity feed retrieved successfully');
    }
    
    /**
     * Get revenue overview for charts
     * 
     * @param DashboardRequest $request
     * @return JsonResponse
     */
    public function revenueOverview(DashboardRequest $request): JsonResponse
    {
        $period = $request->getPeriod(); // Validated: day, week, month, year
        
        $data = $this->getRevenueData($period);
        
        return $this->success($data, 'Revenue overview retrieved successfully');
    }
    
    /**
     * Get sales by category for pie/donut charts
     * 
     * @param DashboardRequest $request
     * @return JsonResponse
     */
    public function salesByCategory(DashboardRequest $request): JsonResponse
    {
        $limit = $request->getLimit();
        
        $data = $this->getSalesByCategory($limit);
        
        return $this->success($data, 'Sales by category retrieved successfully');
    }
    
    /**
     * Aggregate all dashboard statistics
     * 
     * @return array
     */
    private function aggregateStats(): array
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Get customer count (if Sales module exists)
        $customerCount = $this->getCount('customers', $tenantId);
        
        // Get order count (if Sales module exists)
        $orderCount = $this->getCount('sales_orders', $tenantId);
        
        // Get revenue (if Accounting/Sales module exists)
        $revenue = $this->getRevenue($tenantId);
        
        // Get active projects/products count
        $activeCount = $this->getCount('products', $tenantId, ['status' => 'active']);
        
        return [
            'stats' => [
                [
                    'name' => 'Total Customers',
                    'value' => number_format($customerCount),
                    'change' => $this->calculateChange('customers', $tenantId),
                    'changeType' => 'increase',
                ],
                [
                    'name' => 'Total Orders',
                    'value' => number_format($orderCount),
                    'change' => $this->calculateChange('sales_orders', $tenantId),
                    'changeType' => 'increase',
                ],
                [
                    'name' => 'Revenue',
                    'value' => '$' . number_format($revenue, 2),
                    'change' => $this->calculateRevenueChange($tenantId),
                    'changeType' => 'increase',
                ],
                [
                    'name' => 'Active Products',
                    'value' => number_format($activeCount),
                    'change' => $this->calculateChange('products', $tenantId),
                    'changeType' => 'increase',
                ],
            ],
        ];
    }
    
    /**
     * Get count from a table with optional conditions
     * 
     * @param string $table
     * @param int $tenantId
     * @param array $conditions
     * @return int
     */
    private function getCount(string $table, int $tenantId, array $conditions = []): int
    {
        try {
            $query = DB::table($table)->where('tenant_id', $tenantId);
            
            foreach ($conditions as $column => $value) {
                $query->where($column, $value);
            }
            
            return $query->count();
        } catch (\Exception $e) {
            // Table might not exist if module isn't installed
            return 0;
        }
    }
    
    /**
     * Get total revenue
     * 
     * @param int $tenantId
     * @return float
     */
    private function getRevenue(int $tenantId): float
    {
        try {
            // Try to get from sales_orders table
            return (float) DB::table('sales_orders')
                ->where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->sum('total_amount') ?? 0;
        } catch (\Exception $e) {
            // Table might not exist
            return 0;
        }
    }
    
    /**
     * Calculate percentage change compared to previous period
     * 
     * @param string $table
     * @param int $tenantId
     * @return string
     */
    private function calculateChange(string $table, int $tenantId): string
    {
        try {
            $currentMonth = DB::table($table)
                ->where('tenant_id', $tenantId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count();
            
            $previousMonth = DB::table($table)
                ->where('tenant_id', $tenantId)
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->count();
            
            if ($previousMonth === 0) {
                return $currentMonth > 0 ? '+100%' : '0%';
            }
            
            $change = (($currentMonth - $previousMonth) / $previousMonth) * 100;
            $sign = $change >= 0 ? '+' : '';
            
            return $sign . number_format($change, 1) . '%';
        } catch (\Exception $e) {
            return '0%';
        }
    }
    
    /**
     * Calculate revenue change
     * 
     * @param int $tenantId
     * @return string
     */
    private function calculateRevenueChange(int $tenantId): string
    {
        try {
            $currentRevenue = DB::table('sales_orders')
                ->where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('total_amount') ?? 0;
            
            $previousRevenue = DB::table('sales_orders')
                ->where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->sum('total_amount') ?? 0;
            
            if ($previousRevenue === 0) {
                return $currentRevenue > 0 ? '+100%' : '0%';
            }
            
            $change = (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
            $sign = $change >= 0 ? '+' : '';
            
            return $sign . number_format($change, 1) . '%';
        } catch (\Exception $e) {
            return '0%';
        }
    }
    
    /**
     * Format activity description
     * 
     * @param object $activity
     * @return string
     */
    private function formatActivityDescription(object $activity): string
    {
        $entity = str_replace('_', ' ', $activity->entity_type);
        $entity = ucwords($entity);
        
        return match($activity->action) {
            'created' => "{$entity} #{$activity->entity_id} created",
            'updated' => "{$entity} #{$activity->entity_id} updated",
            'deleted' => "{$entity} #{$activity->entity_id} deleted",
            default => "{$activity->action} on {$entity} #{$activity->entity_id}",
        };
    }
    
    /**
     * Get revenue data for charts
     * 
     * SECURITY: Period is validated via DashboardRequest to prevent SQL injection
     * 
     * @param string $period Validated period: day, week, month, or year
     * @return array
     */
    private function getRevenueData(string $period): array
    {
        try {
            $tenantId = auth()->user()->tenant_id;
            
            $query = DB::table('sales_orders')
                ->where('tenant_id', $tenantId)
                ->where('status', 'completed');
            
            // Use safe query builder methods instead of DB::raw() with interpolation
            // Period is already validated in DashboardRequest
            switch ($period) {
                case 'day':
                    $query->whereDate('created_at', '>=', Carbon::now()->subDays(30));
                    // Use query builder groupBy instead of raw SQL
                    $data = $query
                        ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
                        ->groupByRaw('DATE(created_at)')
                        ->orderBy('date')
                        ->get();
                    break;
                    
                case 'week':
                    $query->whereDate('created_at', '>=', Carbon::now()->subWeeks(12));
                    // Use safe groupByRaw with static SQL (no interpolation)
                    $data = $query
                        ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
                        ->groupByRaw('YEAR(created_at), WEEK(created_at)')
                        ->orderBy('date')
                        ->get();
                    break;
                    
                case 'year':
                    $query->whereDate('created_at', '>=', Carbon::now()->subYears(2));
                    $data = $query
                        ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
                        ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                        ->orderBy('date')
                        ->get();
                    break;
                    
                default: // month
                    $query->whereDate('created_at', '>=', Carbon::now()->subMonths(12));
                    $data = $query
                        ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
                        ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                        ->orderBy('date')
                        ->get();
                    break;
            }
            
            return [
                'labels' => $data->pluck('date')->toArray(),
                'values' => $data->pluck('revenue')->toArray(),
            ];
        } catch (\Exception $e) {
            return ['labels' => [], 'values' => []];
        }
    }
    
    /**
     * Get sales by category
     * 
     * @param int $limit
     * @return array
     */
    private function getSalesByCategory(int $limit): array
    {
        try {
            $tenantId = auth()->user()->tenant_id;
            
            $data = DB::table('sales_order_line_items as li')
                ->join('products as p', 'li.product_id', '=', 'p.id')
                ->join('categories as c', 'p.category_id', '=', 'c.id')
                ->where('p.tenant_id', $tenantId)
                ->select('c.name', DB::raw('SUM(li.total) as total'))
                ->groupBy('c.id', 'c.name')
                ->orderByDesc('total')
                ->limit($limit)
                ->get();
            
            return [
                'labels' => $data->pluck('name')->toArray(),
                'values' => $data->pluck('total')->toArray(),
            ];
        } catch (\Exception $e) {
            return ['labels' => [], 'values' => []];
        }
    }
}

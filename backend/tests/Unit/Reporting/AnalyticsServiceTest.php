<?php

namespace Tests\Unit\Reporting;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Reporting\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $analyticsService;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->analyticsService = new AnalyticsService;
    }

    public function test_can_get_all_kpis(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';

        $kpis = $this->analyticsService->getAllKPIs($startDate, $endDate);

        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('total_revenue', $kpis);
        $this->assertArrayHasKey('total_expenses', $kpis);
        $this->assertArrayHasKey('gross_profit_margin', $kpis);
        $this->assertArrayHasKey('net_profit_margin', $kpis);
        $this->assertArrayHasKey('inventory_turnover_ratio', $kpis);
        $this->assertArrayHasKey('days_sales_outstanding', $kpis);
        $this->assertArrayHasKey('order_fulfillment_rate', $kpis);
        $this->assertArrayHasKey('production_efficiency', $kpis);
        $this->assertArrayHasKey('customer_acquisition_cost', $kpis);
        $this->assertArrayHasKey('average_order_value', $kpis);
    }

    public function test_total_revenue_returns_numeric_value(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';

        $revenue = $this->analyticsService->getTotalRevenue($startDate, $endDate);

        $this->assertIsNumeric($revenue);
        $this->assertGreaterThanOrEqual(0, $revenue);
    }

    public function test_gross_profit_margin_returns_percentage(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';

        $margin = $this->analyticsService->getGrossProfitMargin($startDate, $endDate);

        $this->assertIsNumeric($margin);
    }

    public function test_inventory_turnover_ratio_returns_numeric(): void
    {
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';

        $ratio = $this->analyticsService->getInventoryTurnoverRatio($startDate, $endDate);

        $this->assertIsNumeric($ratio);
        $this->assertGreaterThanOrEqual(0, $ratio);
    }
}

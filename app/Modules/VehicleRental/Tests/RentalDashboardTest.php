<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VehicleRental\Services\RentalDashboardService;
use Tests\TestCase;

final class RentalDashboardTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    public function test_dashboard_summary_aggregates_operational_metrics(): void
    {
        [$context] = $this->fixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context): void {
            $service = app(RentalDashboardService::class);
            $summary = $service->getSummary($context->tenantId, $context->organizationUnitId);

            $this->assertArrayHasKey('metrics', $summary);
            $this->assertArrayHasKey('active_customer_agreements', $summary['metrics']);
            $this->assertArrayHasKey('active_owner_agreements', $summary['metrics']);
            $this->assertArrayHasKey('vehicles_in_custody', $summary['metrics']);
            $this->assertArrayHasKey('planned_vehicle_uses', $summary['metrics']);
            $this->assertArrayHasKey('pending_reservations', $summary['metrics']);
            $this->assertArrayHasKey('recent_running_charts', $summary);
        });
    }

    public function test_dashboard_api_endpoint(): void
    {
        $this->loginFixture();

        $response = $this->getJson($this->rentalRoot.'/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'metrics' => [
                        'active_customer_agreements',
                        'active_owner_agreements',
                        'vehicles_in_custody',
                        'planned_vehicle_uses',
                        'pending_reservations',
                        'due_finance_installments',
                        'pending_expenses',
                        'active_vehicle_incidents',
                    ],
                    'recent_running_charts',
                ],
            ]);

        $this->assertIsArray($response->json('data.recent_running_charts'));
    }

    public function test_dashboard_summary_with_running_charts_eager_loads_and_maps_correctly(): void
    {
        $this->mock(\Modules\VehicleRental\Services\RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
            $mock->shouldReceive('assertChart')->zeroOrMoreTimes();
            $mock->shouldReceive('assertDashboard')->zeroOrMoreTimes();
        });

        $this->custodyFixture(function ($context, $use, $facts): void {
            $charts = app(\Modules\VehicleRental\Services\RunningChartService::class);
            $chart = $charts->create($context, (int) $use->id, (int) $use->row_version, $facts);

            $service = app(RentalDashboardService::class);
            $summary = $service->getSummary($context->tenantId, $context->organizationUnitId);

            $this->assertNotEmpty($summary['recent_running_charts']);
            $first = $summary['recent_running_charts'][0];
            $this->assertSame($chart->reference, $first['reference']);
            $this->assertNotEmpty($first['vehicle_label']);
            $this->assertNotEmpty($first['starts_at']);
            $this->assertNotEmpty($first['ends_at']);
            $this->assertSame('draft', $first['status']);
        });
    }
}

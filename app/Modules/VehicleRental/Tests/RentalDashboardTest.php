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
}

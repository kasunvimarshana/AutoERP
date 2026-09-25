<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Services\DriverDirectory;
use Modules\VehicleRental\Services\RentalAuthorization;
use Tests\TestCase;

final class DriverDirectoryTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    public function test_rental_driver_directory_reuses_hr_availability_without_hr_http_permissions(): void
    {
        [$context] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context): void {
            $this->employee($context->tenantId, $context->organizationUnitId, 'DRV-AVAILABLE', 'Available Driver', 'available');
            $this->employee($context->tenantId, $context->organizationUnitId, 'DRV-BUSY', 'Busy Driver', 'busy');

            $authorization = $this->mock(RentalAuthorization::class);
            $authorization->shouldReceive('assertChart')->once()->with($context, RunningChartAction::Create, true);

            $rows = app(DriverDirectory::class)->list($context, 25, 'Driver');
            self::assertSame(['DRV-AVAILABLE'], $rows->getCollection()->pluck('employee_number')->all());
        });
    }

    private function employee(int $tenantId, int $organizationUnitId, string $number, string $name, string $availability): int
    {
        return DB::table('hr_employees')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'employee_number' => $number,
            'display_name' => $name,
            'first_name' => $name,
            'status' => 'active',
            'availability_status' => $availability,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

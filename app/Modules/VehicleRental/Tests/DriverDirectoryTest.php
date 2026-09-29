<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Http\Resources\DriverDirectoryResource;
use Modules\VehicleRental\Services\DriverDirectory;
use Modules\VehicleRental\Services\RentalAuthorization;
use Tests\TestCase;

final class DriverDirectoryTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    public function test_rental_driver_directory_reuses_hr_availability_and_exposes_only_selector_identity(): void
    {
        [$context] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context): void {
            $this->employee($context->tenantId, $context->organizationUnitId, 'DRV-AVAILABLE', 'Available Driver', 'available', 'private@example.test');
            $this->employee($context->tenantId, $context->organizationUnitId, 'DRV-ASSIGNED', 'Assigned Driver', 'assigned', 'assigned@example.test');

            $authorization = $this->mock(RentalAuthorization::class);
            $authorization->shouldReceive('assertChart')->once()->with($context, RunningChartAction::Create, true);

            $rows = app(DriverDirectory::class)->list($context, 25, 'Driver');
            self::assertSame(['DRV-AVAILABLE'], $rows->getCollection()->pluck('employee_number')->all());

            $payload = (new DriverDirectoryResource($rows->getCollection()->first()))->toArray(new Request);
            self::assertSame(['id', 'employee_number', 'code', 'name'], array_keys($payload));
            self::assertSame('DRV-AVAILABLE', $payload['employee_number']);
            self::assertSame('Available Driver', $payload['name']);
            self::assertArrayNotHasKey('email', $payload);
            self::assertArrayNotHasKey('phone', $payload);
            self::assertArrayNotHasKey('mobile', $payload);
        });
    }

    private function employee(int $tenantId, int $organizationUnitId, string $number, string $name, string $availability, string $email): int
    {
        return DB::table('hr_employees')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'employee_number' => $number,
            'display_name' => $name,
            'first_name' => $name,
            'email' => $email,
            'phone' => '0110000000',
            'mobile' => '0770000000',
            'status' => 'active',
            'availability_status' => $availability,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicle;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Services\VehicleAuthorizationService;
use Modules\Vehicle\Services\VehicleQueryService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class VehicleBlockerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $this->mock(VehicleAuthorizationService::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_vehicle_cannot_be_deleted_when_referenced_by_vehicle_rental_uses(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Org Veh '.$suffix,
                'code' => 'ORGV-'.$suffix,
            ]);

            $vehicle = Vehicle::query()->create([
                'tenant_id' => $tenantId,
                'vehicle_number' => 'VN-USE-'.$suffix,
                'registration_number' => 'REG-USE-'.$suffix,
                'status' => 'active',
            ]);

            DB::table('vehicle_rental_uses')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'vehicle_id' => $vehicle->getKey(),
                'vehicle_label_snapshot' => 'REG-USE-'.$suffix,
                'status' => 'allocated',
                'starts_at' => now(),
                'starts_at_input' => '2026-11-22 09:00:00',
                'ends_at' => now()->addDays(2),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->expectException(ConflictHttpException::class);
            $this->expectExceptionMessage('Vehicle is referenced by operational or financial history and cannot be deleted.');

            app(VehicleQueryService::class)->delete($vehicle);
        });
    }

    public function test_unreferenced_vehicle_can_be_deleted(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $suffix = Str::upper(Str::random(6));
            $vehicle = Vehicle::query()->create([
                'tenant_id' => $tenantId,
                'vehicle_number' => 'VN-DEL-'.$suffix,
                'registration_number' => 'REG-DEL-'.$suffix,
                'status' => 'active',
            ]);

            app(VehicleQueryService::class)->delete($vehicle);

            $this->assertSoftDeleted('vehicles', ['id' => $vehicle->getKey()]);
        });
    }

    private function tenant(): int
    {
        $suffix = Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Vehicle Test Tenant',
            'code' => 'veh-test-'.$suffix,
            'slug' => 'vehicle-test-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

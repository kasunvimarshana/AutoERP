<?php

declare(strict_types=1);

namespace Modules\Vehicle\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\User\Models\UserModel;
use Modules\Vehicle\DTOs\CreateVehicleData;
use Modules\Vehicle\DTOs\VehicleMakeData;
use Modules\Vehicle\DTOs\VehicleModelData;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleMake;
use Modules\Vehicle\Models\VehicleModel;
use Modules\Vehicle\Services\VehicleAuthorizationService;
use Modules\Vehicle\Services\VehicleCreationService;
use Modules\Vehicle\Services\VehicleMakeService;
use Modules\Vehicle\Services\VehicleModelService;
use Tests\Support\CurrencyFixture;
use Tests\TestCase;

final class VehicleRowVersionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $this->mock(VehicleAuthorizationService::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_vehicle_api_update_requires_current_row_version_and_rejects_stale_version(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        $this->actingAsTenantUser($tenantId);
        [$make, $model] = $this->masterData($tenantId, $organizationUnitId);
        $vehicle = $this->vehicle($tenantId, $organizationUnitId, (int) $make->getKey(), (int) $model->getKey());
        $vehicleId = (int) $vehicle->getKey();

        $current = $this->tenantGetJson($tenantId, "/api/v1/vehicles/{$vehicleId}?tenant_id={$tenantId}&organization_unit_id={$organizationUnitId}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'row_version']]);
        $rowVersion = (int) $current->json('data.row_version');
        self::assertGreaterThanOrEqual(0, $rowVersion);

        $this->runInTenant($tenantId, fn () => $this->putJson("/api/v1/vehicles/{$vehicleId}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'row_version' => $rowVersion,
            'color' => 'Blue',
        ]))->assertOk()
            ->assertJsonPath('data.color', 'Blue')
            ->assertJsonPath('data.row_version', $rowVersion + 1);

        $this->runInTenant($tenantId, fn () => $this->putJson("/api/v1/vehicles/{$vehicleId}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'row_version' => $rowVersion,
            'color' => 'Red',
        ]))->assertStatus(409)
            ->assertJsonPath('message', 'Vehicle was changed by someone else. Reload before saving.');
    }

    /** @return array{int, int} */
    private function scopeContext(): array
    {
        $suffix = Str::upper(Str::random(5));
        $currencyId = CurrencyFixture::create([
            'name' => 'Vehicle Currency '.$suffix,
            'symbol' => 'VC',
        ]);
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-VEH-RV-'.$suffix,
            'name' => 'Vehicle Tenant '.$suffix,
            'slug' => 'vehicle-tenant-rv-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'base_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Organization '.$suffix,
            'code' => 'ORG-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationUnitId];
    }

    /** @return array{VehicleMake, VehicleModel} */
    private function masterData(int $tenantId, int $organizationUnitId): array
    {
        return $this->runInTenant($tenantId, function () use ($tenantId, $organizationUnitId): array {
            $make = app(VehicleMakeService::class)->create(new VehicleMakeData($tenantId, 'RV-MAKE', 'Row Version Make', $organizationUnitId));
            $model = app(VehicleModelService::class)->create(new VehicleModelData($tenantId, (int) $make->getKey(), 'RV-MODEL', 'Row Version Model', $organizationUnitId));

            return [$make, $model];
        });
    }

    private function vehicle(int $tenantId, int $organizationUnitId, int $makeId, int $modelId): Vehicle
    {
        return $this->runInTenant($tenantId, fn (): Vehicle => app(VehicleCreationService::class)->create(new CreateVehicleData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            vehicleNumber: 'VEH-RV',
            vehicleMakeId: $makeId,
            vehicleModelId: $modelId,
            status: VehicleStatus::Active,
        )));
    }

    private function actingAsTenantUser(int $tenantId): void
    {
        $userId = (int) \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Vehicle',
            'last_name' => 'Row Version Tester',
            'email' => 'vehicle-row-version-'.Str::lower(Str::random(8)).'@example.test',
            'password' => 'secret-password',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->runInTenant($tenantId, fn (): UserModel => UserModel::query()->findOrFail($userId)));
    }

    private function runInTenant(int $tenantId, callable $callback): mixed
    {
        return $this->withTenantExecutionContext($tenantId, $callback);
    }
}

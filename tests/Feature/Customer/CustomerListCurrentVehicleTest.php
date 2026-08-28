<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Customer\Services\CustomerAuthorizationService;
use Modules\User\Models\UserModel;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class CustomerListCurrentVehicleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $this->mock(CustomerAuthorizationService::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_customer_list_returns_only_current_visible_vehicles(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $this->authenticate($tenantId);
        $customerId = $this->customer($tenantId, $organizationUnitId, 'CUS-VEHICLE');
        $customerWithoutVehiclesId = $this->customer($tenantId, $organizationUnitId, 'CUS-EMPTY');
        [$makeId, $modelId] = $this->vehicleIdentity($tenantId, $organizationUnitId);

        $currentVehicleId = $this->vehicle(
            $tenantId,
            $organizationUnitId,
            $makeId,
            $modelId,
            'VEH-CURRENT',
            'WP-CAB-1234',
        );
        $endedVehicleId = $this->vehicle(
            $tenantId,
            $organizationUnitId,
            $makeId,
            $modelId,
            'VEH-ENDED',
            'WP-CAB-5678',
        );
        $deletedVehicleId = $this->vehicle(
            $tenantId,
            $organizationUnitId,
            $makeId,
            $modelId,
            'VEH-DELETED',
            'WP-CAB-9999',
            deleted: true,
        );

        $this->ownership($tenantId, $organizationUnitId, $currentVehicleId, $customerId, true);
        $this->ownership($tenantId, $organizationUnitId, $endedVehicleId, $customerId, false);
        $this->ownership($tenantId, $organizationUnitId, $deletedVehicleId, $customerId, true);

        $response = $this->tenantGetJson($tenantId, '/api/v1/customers?'.http_build_query([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ]))->assertOk();

        $customers = collect($response->json('data'))->keyBy('id');
        $this->assertSame([[
            'id' => $currentVehicleId,
            'registration_number' => 'WP-CAB-1234',
        ]], $customers->get($customerId)['current_vehicles']);
        $this->assertSame([], $customers->get($customerWithoutVehiclesId)['current_vehicles']);

        $this->tenantGetJson($tenantId, '/api/v1/customers?'.http_build_query([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'search' => 'WP-CAB-1234',
        ]))->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $customerId);

        $this->tenantGetJson($tenantId, '/api/v1/customers?'.http_build_query([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'search' => 'VEH-CURRENT',
        ]))->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $customerId);

        $this->tenantGetJson($tenantId, '/api/v1/customers?'.http_build_query([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'search' => 'WP-CAB-5678',
        ]))->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @return array{int, int}
     */
    private function scope(): array
    {
        $suffix = Str::upper(Str::random(8));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Main',
            'code' => 'MAIN',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationUnitId];
    }

    private function authenticate(int $tenantId): void
    {
        $userId = (int) TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Customer',
            'last_name' => 'Tester',
            'email' => 'customer-list-'.Str::lower(Str::random(8)).'@example.test',
            'password' => bcrypt('secret-password'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = $this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        );

        $this->actingAs($user, (string) config('module-auth.protected_route_guard', 'auth-api'));
    }

    private function customer(int $tenantId, int $organizationUnitId, string $code): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => 'NUM-'.$code,
            'code' => $code,
            'name' => 'Customer '.$code,
            'customer_type' => 'retail',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{int, int}
     */
    private function vehicleIdentity(int $tenantId, int $organizationUnitId): array
    {
        $makeId = (int) DB::table('vehicle_makes')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => 'TOYOTA',
            'name' => 'Toyota',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $modelId = (int) DB::table('vehicle_models')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_make_id' => $makeId,
            'code' => 'COROLLA',
            'name' => 'Corolla',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$makeId, $modelId];
    }

    private function vehicle(
        int $tenantId,
        int $organizationUnitId,
        int $makeId,
        int $modelId,
        string $vehicleNumber,
        string $registrationNumber,
        bool $deleted = false,
    ): int {
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_number' => $vehicleNumber,
            'code' => $vehicleNumber,
            'vehicle_make_id' => $makeId,
            'vehicle_model_id' => $modelId,
            'registration_number' => $registrationNumber,
            'status' => 'active',
            'deleted_at' => $deleted ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ownership(
        int $tenantId,
        int $organizationUnitId,
        int $vehicleId,
        int $customerId,
        bool $current,
    ): void {
        DB::table('vehicle_ownerships')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_id' => $vehicleId,
            'owner_type' => 'customer',
            'owner_id' => $customerId,
            'owner_key' => 'customer:'.$customerId,
            'owner_code_snapshot' => 'CUS-VEHICLE',
            'owner_name_snapshot' => 'Customer CUS-VEHICLE',
            'ownership_type' => 'customer_owned',
            'started_at' => now()->subDay(),
            'ended_at' => $current ? null : now(),
            'is_current' => $current,
            'current_guard' => $current ? 1 : null,
            'active_guard' => $current ? 1 : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

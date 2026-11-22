<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\Customer\Models\Customer;
use Modules\Hr\Models\HrEmployee;
use Modules\User\Models\UserModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleService\DTOs\VehicleServiceJobData;
use Modules\VehicleService\Models\VehicleServiceJobType;
use Modules\VehicleService\Services\VehicleServiceJobService;
use Modules\VehicleService\Services\VehicleServiceJobTypeService;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class VehicleServiceJobTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_job_types_index_seeds_and_returns_system_defaults(): void
    {
        $context = $this->createContext();

        $response = $this->tenantGetJson(
            $context['tenant_id'],
            '/api/v1/vehicle-service/job-types?tenant_id='.$context['tenant_id'].'&organization_unit_id='.$context['organization_unit_id'],
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'row_version', 'tenant_id', 'code', 'name',
                    'description', 'tracks_mileage', 'is_active', 'is_system', 'sort_order',
                ],
            ],
            'meta',
        ]);

        $data = $response->json('data');
        $this->assertCount(5, $data);
        $codes = array_column($data, 'code');
        $this->assertContains('full_service', $codes);
        $this->assertContains('body_wash', $codes);
        $this->assertContains('oil_change', $codes);
        $this->assertContains('wheel_alignment', $codes);
        $this->assertContains('accessories', $codes);

        $fullService = collect($data)->firstWhere('code', 'full_service');
        $this->assertTrue($fullService['is_system']);
        $this->assertTrue($fullService['tracks_mileage']);

        $bodyWash = collect($data)->firstWhere('code', 'body_wash');
        $this->assertTrue($bodyWash['is_system']);
        $this->assertFalse($bodyWash['tracks_mileage']);
    }

    public function test_job_types_lookup_returns_only_active_items(): void
    {
        $context = $this->createContext();
        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context): void {
            $service = app(VehicleServiceJobTypeService::class);
            $service->ensureDefaults($context['tenant_id'], $context['organization_unit_id']);

            // Create an inactive type
            VehicleServiceJobType::query()->create([
                'tenant_id' => $context['tenant_id'],
                'organization_unit_id' => $context['organization_unit_id'],
                'code' => 'inactive_service',
                'name' => 'Inactive Service',
                'tracks_mileage' => false,
                'is_active' => false,
                'is_system' => false,
                'sort_order' => 99,
                'row_version' => 1,
            ]);
        });

        $response = $this->tenantGetJson(
            $context['tenant_id'],
            '/api/v1/vehicle-service/job-types/lookup?tenant_id='.$context['tenant_id'].'&organization_unit_id='.$context['organization_unit_id'],
        );

        $response->assertOk();
        $codes = array_column($response->json('data'), 'code');
        $this->assertNotContains('inactive_service', $codes);
        $this->assertContains('full_service', $codes);
    }

    public function test_custom_job_type_can_be_created(): void
    {
        $context = $this->createContext();

        $response = $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/job-types', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => 'express_detailing',
            'name' => 'Express Detailing',
            'description' => 'Fast interior and exterior car cleaning.',
            'tracks_mileage' => false,
            'is_active' => true,
            'sort_order' => 60,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.code', 'express_detailing');
        $response->assertJsonPath('data.name', 'Express Detailing');
        $response->assertJsonPath('data.is_system', false);
        $response->assertJsonPath('data.tracks_mileage', false);
        $response->assertJsonPath('data.row_version', 1);

        $this->assertDatabaseHas('vehicle_service_job_types', [
            'tenant_id' => $context['tenant_id'],
            'code' => 'express_detailing',
            'name' => 'Express Detailing',
            'is_system' => false,
        ]);
    }

    public function test_job_type_code_uniqueness_within_tenant(): void
    {
        $context = $this->createContext();

        $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/job-types', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => 'custom_paint',
            'name' => 'Custom Paint',
            'sort_order' => 70,
        ])->assertStatus(201);

        // Duplicate code in same tenant fails
        $duplicateResponse = $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/job-types', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => 'custom_paint',
            'name' => 'Another Paint',
            'sort_order' => 71,
        ]);

        $duplicateResponse->assertStatus(422);
    }

    public function test_job_type_can_be_updated_with_optimistic_locking(): void
    {
        $context = $this->createContext();

        $createResponse = $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/job-types', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => 'tuning',
            'name' => 'Engine Tuning',
            'tracks_mileage' => true,
            'sort_order' => 80,
        ]);
        $typeId = (int) $createResponse->json('data.id');

        // Successful update with current version
        $updateResponse = $this->tenantPutJson($context['tenant_id'], "/api/v1/vehicle-service/job-types/{$typeId}", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => 1,
            'code' => 'tuning',
            'name' => 'Performance Engine Tuning',
            'tracks_mileage' => true,
            'sort_order' => 85,
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.name', 'Performance Engine Tuning');
        $updateResponse->assertJsonPath('data.row_version', 2);

        // Stale update fails with 422
        $staleResponse = $this->tenantPutJson($context['tenant_id'], "/api/v1/vehicle-service/job-types/{$typeId}", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => 1,
            'code' => 'tuning',
            'name' => 'Stale Performance Tuning',
            'tracks_mileage' => true,
            'sort_order' => 85,
        ]);

        $staleResponse->assertStatus(422);
    }

    public function test_system_job_type_code_cannot_be_modified(): void
    {
        $context = $this->createContext();
        $fullService = $this->withTenantExecutionContext($context['tenant_id'], function () use ($context): VehicleServiceJobType {
            $service = app(VehicleServiceJobTypeService::class);
            $service->ensureDefaults($context['tenant_id'], $context['organization_unit_id']);

            return VehicleServiceJobType::query()
                ->where('tenant_id', $context['tenant_id'])
                ->where('code', 'full_service')
                ->firstOrFail();
        });

        $response = $this->tenantPutJson($context['tenant_id'], "/api/v1/vehicle-service/job-types/{$fullService->id}", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => (int) $fullService->row_version,
            'code' => 'renamed_full_service',
            'name' => 'Full Service Renamed',
        ]);

        $response->assertStatus(422);
    }

    public function test_system_job_type_cannot_be_deleted(): void
    {
        $context = $this->createContext();
        $fullService = $this->withTenantExecutionContext($context['tenant_id'], function () use ($context): VehicleServiceJobType {
            $service = app(VehicleServiceJobTypeService::class);
            $service->ensureDefaults($context['tenant_id'], $context['organization_unit_id']);

            return VehicleServiceJobType::query()
                ->where('tenant_id', $context['tenant_id'])
                ->where('code', 'full_service')
                ->firstOrFail();
        });

        $response = $this->tenantDeleteJson($context['tenant_id'], "/api/v1/vehicle-service/job-types/{$fullService->id}", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => (int) $fullService->row_version,
        ]);

        $response->assertStatus(422);
    }

    public function test_job_type_in_use_by_service_job_cannot_be_deleted(): void
    {
        $context = $this->createContext();

        $createResponse = $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/job-types', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => 'glass_repair',
            'name' => 'Glass Repair',
            'tracks_mileage' => false,
        ]);
        $typeId = (int) $createResponse->json('data.id');

        // Create job using this type
        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $typeId): void {
            $jobService = app(VehicleServiceJobService::class);
            $jobService->create(new VehicleServiceJobData(
                tenantId: $context['tenant_id'],
                jobDate: '2026-06-15',
                customerId: $context['customer_id'],
                vehicleId: $context['vehicle_id'],
                type: 'glass_repair',
                jobTypeId: $typeId,
                organizationUnitId: $context['organization_unit_id'],
                manualJobCard: 'MJC-GLASS-1',
                supervisorEmployeeId: $context['supervisor_id'],
                createdBy: $context['user_id'],
            ));
        });

        // Try deleting the job type
        $response = $this->tenantDeleteJson($context['tenant_id'], "/api/v1/vehicle-service/job-types/{$typeId}", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_unreferenced_custom_job_type_can_be_deleted(): void
    {
        $context = $this->createContext();

        $createResponse = $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/job-types', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => 'ceramic_coating',
            'name' => 'Ceramic Coating',
            'tracks_mileage' => false,
        ]);
        $typeId = (int) $createResponse->json('data.id');

        $deleteResponse = $this->tenantDeleteJson($context['tenant_id'], "/api/v1/vehicle-service/job-types/{$typeId}", [
            'tenant_id' => $context['tenant_id'],
            'expected_version' => 1,
        ]);

        $deleteResponse->assertStatus(204);

        $this->assertSoftDeleted('vehicle_service_job_types', [
            'id' => $typeId,
            'tenant_id' => $context['tenant_id'],
        ]);
    }

    public function test_service_job_creation_respects_custom_job_type_mileage_requirement(): void
    {
        $context = $this->createContext();

        // 1. Create type requiring mileage
        $mileageTypeResponse = $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/job-types', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => 'brake_service',
            'name' => 'Brake Service',
            'tracks_mileage' => true,
        ]);
        $mileageTypeId = (int) $mileageTypeResponse->json('data.id');

        // Creating job without odometer should fail
        $noOdoResponse = $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/jobs', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'job_date' => '2026-06-15',
            'customer_id' => $context['customer_id'],
            'vehicle_id' => $context['vehicle_id'],
            'job_type_id' => $mileageTypeId,
            'manual_job_card' => 'MJC-BRAKE-1',
            'supervisor_employee_id' => $context['supervisor_id'],
        ]);
        $noOdoResponse->assertStatus(422);

        // Creating job with odometer succeeds
        $withOdoResponse = $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/jobs', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'job_date' => '2026-06-15',
            'customer_id' => $context['customer_id'],
            'vehicle_id' => $context['vehicle_id'],
            'job_type_id' => $mileageTypeId,
            'manual_job_card' => 'MJC-BRAKE-2',
            'supervisor_employee_id' => $context['supervisor_id'],
            'odometer_reading' => '45000.000000',
        ]);
        $withOdoResponse->assertStatus(201);
        $withOdoResponse->assertJsonPath('data.job_type_id', $mileageTypeId);
        $withOdoResponse->assertJsonPath('data.type', 'brake_service');
        $withOdoResponse->assertJsonPath('data.type_label', 'Brake Service');

        // 2. Create type prohibiting mileage
        $noMileageTypeResponse = $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/job-types', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => 'window_tint',
            'name' => 'Window Tinting',
            'tracks_mileage' => false,
        ]);
        $noMileageTypeId = (int) $noMileageTypeResponse->json('data.id');

        // Creating job with odometer should fail
        $forbiddenOdoResponse = $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/jobs', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'job_date' => '2026-06-15',
            'customer_id' => $context['customer_id'],
            'vehicle_id' => $context['vehicle_id'],
            'job_type_id' => $noMileageTypeId,
            'manual_job_card' => 'MJC-TINT-1',
            'supervisor_employee_id' => $context['supervisor_id'],
            'odometer_reading' => '45000.000000',
        ]);
        $forbiddenOdoResponse->assertStatus(422);

        // Creating job without odometer succeeds
        $validNoOdoResponse = $this->tenantPostJson($context['tenant_id'], '/api/v1/vehicle-service/jobs', [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'job_date' => '2026-06-15',
            'customer_id' => $context['customer_id'],
            'vehicle_id' => $context['vehicle_id'],
            'job_type_id' => $noMileageTypeId,
            'manual_job_card' => 'MJC-TINT-2',
            'supervisor_employee_id' => $context['supervisor_id'],
        ]);
        $validNoOdoResponse->assertStatus(201);
        $validNoOdoResponse->assertJsonPath('data.job_type_id', $noMileageTypeId);
        $validNoOdoResponse->assertJsonPath('data.type', 'window_tint');
    }

    /** @return array{tenant_id:int, organization_unit_id:int, user_id:int, customer_id:int, vehicle_id:int, supervisor_id:int, customer:Customer, vehicle:Vehicle, supervisor:HrEmployee} */
    private function createContext(): array
    {
        $now = now();
        $suffix = Str::lower(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.$suffix,
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Job type test setup.',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $organizationUnitId = (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Main Workshop',
            'code' => 'WS-'.$suffix,
            'path' => '/ws-'.$suffix,
            'is_active' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Service',
            'last_name' => 'Admin',
            'email' => 'admin-'.$suffix.'@example.test',
            'status' => 'active',
        ]);

        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationUnitId, $userId, $suffix, $now): array {
            $customerId = (int) DB::table('customers')->insertGetId([
                'tenant_id' => $tenantId,
                'customer_number' => 'CUST-'.$suffix,
                'code' => 'C-'.$suffix,
                'name' => 'Customer '.$suffix,
                'display_name' => 'Customer '.$suffix,
                'customer_type' => 'individual',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $customer = Customer::query()->findOrFail($customerId);

            $vehicleId = (int) DB::table('vehicles')->insertGetId([
                'tenant_id' => $tenantId,
                'vehicle_number' => 'V-'.$suffix,
                'registration_number' => 'REG-'.$suffix,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('vehicle_ownerships')->insert([
                'row_version' => 1,
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'vehicle_id' => $vehicleId,
                'owner_type' => 'customer',
                'owner_id' => $customerId,
                'owner_key' => 'customer:'.$customerId,
                'owner_code_snapshot' => 'C-'.$suffix,
                'owner_name_snapshot' => 'Customer '.$suffix,
                'ownership_type' => 'customer_owned',
                'started_at' => $now,
                'is_current' => true,
                'current_guard' => 1,
                'active_guard' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $vehicle = Vehicle::query()->findOrFail($vehicleId);

            $designationId = (int) DB::table('hr_designations')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'SUPERVISOR',
                'name' => 'Workshop Supervisor',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $employeeId = (int) DB::table('hr_employees')->insertGetId([
                'tenant_id' => $tenantId,
                'employee_number' => 'EMP-'.$suffix,
                'code' => 'EMP-'.$suffix,
                'first_name' => 'Supervisor',
                'last_name' => 'One',
                'display_name' => 'Supervisor One',
                'designation_id' => $designationId,
                'status' => 'active',
                'availability_status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $supervisor = HrEmployee::query()->findOrFail($employeeId);

            // Provision tenant permissions
            app(TenantAccessProvisionerInterface::class)->provision($tenantId);

            // Assign super admin role to user
            $roleId = DB::table('roles')->where('tenant_id', $tenantId)->value('id');
            if ($roleId !== null) {
                DB::table('user_roles')->insert([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'row_version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $user = UserModel::query()->findOrFail($userId);
            $this->actingAs($user, (string) config('module-auth.protected_route_guard', 'auth-api'));

            return [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'user_id' => $userId,
                'customer_id' => $customerId,
                'vehicle_id' => $vehicleId,
                'supervisor_id' => $employeeId,
                'customer' => $customer,
                'vehicle' => $vehicle,
                'supervisor' => $supervisor,
            ];
        });
    }
}

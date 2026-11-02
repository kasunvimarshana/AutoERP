<?php

declare(strict_types=1);

namespace Modules\Hr\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Hr\Models\HrEmployee;
use Modules\User\Models\UserModel;
use Tests\TestCase;

final class HrDriverCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_driver_crud_lifecycle_and_license_management(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationId] = $this->scope();
        $this->actingAsHrUser($tenantId);

        // 1. Create Driver via POST /api/v1/hr/drivers
        $createResponse = $this->tenantPostJson($tenantId, '/api/v1/hr/drivers', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationId,
            'code' => 'DRV-1001',
            'first_name' => 'Sunil',
            'last_name' => 'Perera',
            'display_name' => 'Sunil Perera',
            'nic_passport' => '851234567V',
            'mobile' => '0771234567',
            'email' => 'sunil.driver@example.com',
            'driving_license_number' => 'B1234567',
            'driving_license_classes' => ['light_vehicle', 'dual_purpose'],
            'driving_license_issued' => '2020-01-15',
            'driving_license_expiry' => '2030-01-15',
            'emergency_contact_name' => 'Kamala Perera',
            'emergency_contact_phone' => '0779876543',
            'default_daily_rate' => '3500.00',
            'default_hourly_rate' => '450.00',
            'work_start_time' => '07:30',
            'work_end_time' => '17:30',
            'standard_shift_hours' => 10,
            'status' => 'active',
            'availability_status' => 'available',
        ]);

        $createResponse->assertCreated();
        $driverId = (int) $createResponse->json('data.id');
        $this->assertGreaterThan(0, $driverId);
        $this->assertSame('Sunil Perera', $createResponse->json('data.display_name'));
        $this->assertSame('B1234567', $createResponse->json('data.driving_license_number'));
        $this->assertSame('851234567V', $createResponse->json('data.nic_passport'));
        $this->assertSame('valid', $createResponse->json('data.license_status'));
        $this->assertSame('07:30', $createResponse->json('data.work_start_time'));
        $this->assertSame('17:30', $createResponse->json('data.work_end_time'));
        $this->assertEquals(10, $createResponse->json('data.standard_shift_hours'));

        // 2. Read Driver List via GET /api/v1/hr/drivers
        $listResponse = $this->tenantGetJson($tenantId, "/api/v1/hr/drivers?tenant_id={$tenantId}&organization_unit_id={$organizationId}&search=Sunil");
        $listResponse->assertOk();
        $listResponse->assertJsonCount(1, 'data');
        $this->assertSame('Sunil Perera', $listResponse->json('data.0.name'));
        $this->assertSame('B1234567', $listResponse->json('data.0.driving_license_number'));

        // 3. Read Single Driver Detail via GET /api/v1/hr/drivers/{id}
        $showResponse = $this->tenantGetJson($tenantId, "/api/v1/hr/drivers/{$driverId}?tenant_id={$tenantId}&organization_unit_id={$organizationId}");
        $showResponse->assertOk();
        $this->assertSame('Kamala Perera', $showResponse->json('data.emergency_contact_name'));
        $this->assertSame('3500.000000', $showResponse->json('data.default_daily_rate'));
        $this->assertSame('07:30', $showResponse->json('data.work_start_time'));
        $this->assertSame('17:30', $showResponse->json('data.work_end_time'));
        $this->assertEquals(10, $showResponse->json('data.standard_shift_hours'));

        // 4. Update Driver via PUT /api/v1/hr/drivers/{id}
        $updateResponse = $this->tenantPutJson($tenantId, "/api/v1/hr/drivers/{$driverId}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationId,
            'first_name' => 'Sunil',
            'last_name' => 'Perera Fernando',
            'driving_license_number' => 'B1234567-RENEWED',
            'driving_license_expiry' => '2032-01-15',
            'mobile' => '0779998888',
            'work_start_time' => '06:30',
            'work_end_time' => '18:30',
            'standard_shift_hours' => 12,
        ]);
        $updateResponse->assertOk();
        $this->assertSame('B1234567-RENEWED', $updateResponse->json('data.driving_license_number'));
        $this->assertSame('2032-01-15', $updateResponse->json('data.driving_license_expiry'));
        $this->assertSame('06:30', $updateResponse->json('data.work_start_time'));
        $this->assertSame('18:30', $updateResponse->json('data.work_end_time'));
        $this->assertEquals(12, $updateResponse->json('data.standard_shift_hours'));

        // 5. Change Driver Status via PATCH /api/v1/hr/drivers/{id}/status
        $statusResponse = $this->tenantPatchJson($tenantId, "/api/v1/hr/drivers/{$driverId}/status", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationId,
            'status' => 'on_leave',
            'availability_status' => 'on_leave',
            'reason' => 'Family vacation',
        ]);
        $statusResponse->assertOk();
        $this->assertSame('on_leave', $statusResponse->json('data.status'));
        $this->assertSame('on_leave', $statusResponse->json('data.availability_status'));

        // 6. Test Driver Lookup via GET /api/v1/hr/drivers/lookup
        // Change back to active to appear in lookup
        $this->tenantPatchJson($tenantId, "/api/v1/hr/drivers/{$driverId}/status", [
            'tenant_id' => $tenantId,
            'status' => 'active',
            'availability_status' => 'available',
        ]);

        $lookupResponse = $this->tenantGetJson($tenantId, "/api/v1/hr/drivers/lookup?tenant_id={$tenantId}&search=Sunil");
        $lookupResponse->assertOk();
        $lookupResponse->assertJsonCount(1, 'data');
        $this->assertSame($driverId, $lookupResponse->json('data.0.id'));

        // 7. Delete Driver via DELETE /api/v1/hr/drivers/{id}
        $deleteResponse = $this->tenantDeleteJson($tenantId, "/api/v1/hr/drivers/{$driverId}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationId,
        ]);
        $deleteResponse->assertOk();

        $this->assertSoftDeleted('hr_employees', ['id' => $driverId]);
    }

    public function test_driver_deletion_is_blocked_when_assigned_to_active_vehicle_allocation(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationId] = $this->scope();
        $this->actingAsHrUser($tenantId);

        $driver = $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationId): HrEmployee {
            return app(\Modules\Hr\Services\HrDriverService::class)->create($tenantId, $organizationId, [
                'first_name' => 'Mahinda',
                'last_name' => 'Silva',
                'driving_license_number' => 'B9988776',
            ], null);
        });

        // Create vehicle & allocation with this driver
        $vehicleId = (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_number' => 'CAB-4321',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerCurrencyId = (int) DB::table('currencies')->value('id');
        $customerId = (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUS-'.uniqid(),
            'code' => 'CUS-TEST',
            'name' => 'Test Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'default_currency_id' => $customerCurrencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $agreementId = (int) DB::table('rental_agreements')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'agreement_number' => 'AGR-'.uniqid(),
            'agreement_kind' => 'customer_rental',
            'agreement_date' => now()->toDateString(),
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->addMonth()->endOfDay(),
            'legal_context' => 'company',
            'rental_mode' => 'with_driver',
            'billing_cycle' => 'monthly',
            'billing_basis' => 'calendar_month',
            'proration_rule' => 'exact_day_count',
            'payment_term_days' => 30,
            'currency_id' => $customerCurrencyId,
            'billing_timezone' => 'Asia/Colombo',
            'status' => 'active',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $allocationId = (int) DB::table('rental_vehicle_allocations')->insertGetId([
            'tenant_id' => $tenantId,
            'agreement_id' => $agreementId,
            'vehicle_id' => $vehicleId,
            'allocation_number' => 'ALC-'.uniqid(),
            'vehicle_source_type' => 'company_owned',
            'allocated_from' => now()->startOfDay(),
            'allocated_to' => now()->addMonth()->endOfDay(),
            'status' => 'active',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert active driver assignment
        DB::table('rental_driver_assignments')->insert([
            'tenant_id' => $tenantId,
            'agreement_id' => $agreementId,
            'vehicle_allocation_id' => $allocationId,
            'employee_id' => $driver->getKey(),
            'assignment_role' => 'primary',
            'assigned_from' => now()->startOfDay(),
            'assigned_to' => now()->addDays(15)->endOfDay(),
            'is_primary' => true,
            'status' => 'active',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attempt deletion - MUST fail because driver is actively assigned
        $response = $this->tenantDeleteJson($tenantId, "/api/v1/hr/drivers/{$driver->getKey()}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationId,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('currently assigned to an active vehicle rental allocation', $response->json('message'));
        $this->assertDatabaseHas('hr_employees', [
            'id' => $driver->getKey(),
            'deleted_at' => null,
        ]);
    }

    private function scope(): array
    {
        $suffix = Str::upper(Str::random(8));
        $currencyId = (int) DB::table('currencies')->insertGetId([
            'row_version' => 1,
            'code' => 'D'.substr($suffix, 0, 4),
            'name' => "Driver Currency {$suffix}",
            'symbol' => 'D',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => "TEN-DRV-{$suffix}",
            'name' => "Driver Tenant {$suffix}",
            'slug' => 'drv-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'base_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationId = (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => "Driver Org {$suffix}",
            'code' => "ORG-{$suffix}",
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationId, $currencyId];
    }

    private function actingAsHrUser(int $tenantId): void
    {
        $userId = \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'email' => 'hr-driver-admin-'.Str::lower(Str::random(8)).'@example.test',
        ]);

        $user = $this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        );

        $this->actingAs($user, (string) config('module-auth.protected_route_guard', 'auth-api'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\HR\Application\Contracts\UpdateAttendanceRecordServiceInterface;
use Modules\HR\Domain\Exceptions\AttendanceRecordNotFoundException;
use Tests\TestCase;

class HRAttendanceRecordIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_service_rejects_cross_tenant_attendance_mutation(): void
    {
        $recordId = 7001;
        $ownerTenantId = 301;
        $wrongTenantId = 302;
        $employeeId = 9001;
        $userId = 9101;

        $this->seedTenantAndUser($ownerTenantId, $userId, 'attendance-owner@example.com');
        $this->seedTenantAndUser($wrongTenantId, 9102, 'attendance-wrong@example.com');
        $this->insertEmployeeRow($employeeId, $ownerTenantId, $userId);

        DB::table('hr_attendance_records')->insert([
            'id' => $recordId,
            'tenant_id' => $ownerTenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'employee_id' => $employeeId,
            'attendance_date' => '2026-04-29',
            'check_in' => '2026-04-29 09:00:00',
            'check_out' => '2026-04-29 17:30:00',
            'break_duration' => 30,
            'worked_minutes' => 480,
            'overtime_minutes' => 0,
            'status' => 'present',
            'shift_id' => null,
            'remarks' => 'Original attendance entry',
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        /** @var UpdateAttendanceRecordServiceInterface $updateService */
        $updateService = app(UpdateAttendanceRecordServiceInterface::class);

        app()->instance('current_tenant_id', $wrongTenantId);

        try {
            $updateService->execute([
                'id' => $recordId,
                'tenant_id' => $wrongTenantId,
                'employee_id' => $employeeId,
                'attendance_date' => '2026-04-29',
                'check_in' => '2026-04-29 10:00:00',
                'check_out' => '2026-04-29 18:00:00',
                'break_duration' => 15,
                'status' => 'late',
                'remarks' => 'Cross-tenant mutation attempt',
            ]);

            $this->fail('Expected cross-tenant attendance update to be rejected.');
        } catch (AttendanceRecordNotFoundException) {
            $this->assertDatabaseHas('hr_attendance_records', [
                'id' => $recordId,
                'tenant_id' => $ownerTenantId,
                'status' => 'present',
                'remarks' => 'Original attendance entry',
            ]);
        }
    }

    private function insertEmployeeRow(int $id, int $tenantId, int $userId): void
    {
        DB::table('employees')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'employee_code' => 'EMP-'.$tenantId.'-'.$id,
            'org_unit_id' => null,
            'job_title' => 'Attendance Analyst',
            'hire_date' => '2024-01-01',
            'termination_date' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedTenantAndUser(int $tenantId, int $userId, string $email): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant '.$tenantId,
            'slug' => 'tenant-'.$tenantId,
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('users')->insert([
            'id' => $userId,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'first_name' => 'Attendance',
            'last_name' => (string) $userId,
            'email' => $email,
            'email_verified_at' => null,
            'password' => 'hashed-password',
            'phone' => null,
            'avatar' => null,
            'status' => 'active',
            'preferences' => null,
            'address' => null,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}

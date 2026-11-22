<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\Hr;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Services\EmployeeQueryService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class EmployeeBlockerTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_be_deleted_when_subordinates_report_to_them(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'HR Branch',
                'code' => 'HRB-'.Str::upper(Str::random(4)),
            ]);

            $manager = $this->createEmployee($tenantId, $orgId, 'Manager John', 'EMP-MGR-01');
            $subordinate = $this->createEmployee($tenantId, $orgId, 'Worker Bob', 'EMP-WRK-01', $manager->id);

            $service = app(EmployeeQueryService::class);

            $this->expectException(ConflictHttpException::class);
            $this->expectExceptionMessage('Employee cannot be deleted while other employees report to them.');

            $service->delete($manager, (int) $manager->row_version);
        });
    }

    public function test_employee_cannot_be_deleted_when_assigned_as_supervisor_in_service_jobs(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Fleet Branch',
                'code' => 'FLT-'.Str::upper(Str::random(4)),
            ]);

            $supervisor = $this->createEmployee($tenantId, $orgId, 'Supervisor Bob', 'EMP-SUP-01');

            $customer = \Modules\Customer\Models\Customer::query()->create([
                'tenant_id' => $tenantId,
                'customer_number' => 'CN-SUP-'.Str::upper(Str::random(6)),
                'customer_type' => 'individual',
                'name' => 'Supervisor Customer',
                'code' => 'CUS-SUP-'.Str::upper(Str::random(6)),
                'status' => 'active',
            ]);

            $vehicle = \Modules\Vehicle\Models\Vehicle::query()->create([
                'tenant_id' => $tenantId,
                'vehicle_number' => 'VN-SUP-'.Str::upper(Str::random(6)),
                'registration_number' => 'REG-SUP-'.Str::upper(Str::random(6)),
                'status' => 'active',
            ]);

            DB::table('vehicle_service_jobs')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'job_number' => 'JOB-SUP-'.Str::upper(Str::random(6)),
                'vehicle_id' => $vehicle->getKey(),
                'customer_id' => $customer->getKey(),
                'supervisor_employee_id' => $supervisor->id,
                'job_date' => now()->toDateString(),
                'status' => 'draft',
                'type' => 'full_service',
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $service = app(EmployeeQueryService::class);

            $this->expectException(ConflictHttpException::class);
            $this->expectExceptionMessage('Employee cannot be deleted while assigned as supervisor in service jobs.');

            $service->delete($supervisor, (int) $supervisor->row_version);
        });
    }

    public function test_employee_cannot_be_deleted_when_assigned_to_service_job_lines(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Service Branch',
                'code' => 'SVC-'.Str::upper(Str::random(4)),
            ]);

            $technician = $this->createEmployee($tenantId, $orgId, 'Tech Sam', 'EMP-TCH-01');

            $customer = \Modules\Customer\Models\Customer::query()->create([
                'tenant_id' => $tenantId,
                'customer_number' => 'CN-SVC-'.Str::upper(Str::random(6)),
                'customer_type' => 'individual',
                'name' => 'Service Customer',
                'code' => 'CUS-SVC-'.Str::upper(Str::random(6)),
                'status' => 'active',
            ]);

            $vehicle = \Modules\Vehicle\Models\Vehicle::query()->create([
                'tenant_id' => $tenantId,
                'vehicle_number' => 'VN-SVC-'.Str::upper(Str::random(6)),
                'registration_number' => 'REG-SVC-'.Str::upper(Str::random(6)),
                'status' => 'active',
            ]);

            $jobId = DB::table('vehicle_service_jobs')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'job_number' => 'JOB-'.Str::upper(Str::random(6)),
                'vehicle_id' => $vehicle->getKey(),
                'customer_id' => $customer->getKey(),
                'job_date' => now()->toDateString(),
                'status' => 'draft',
                'type' => 'full_service',
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lineId = DB::table('vehicle_service_job_lines')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'vehicle_service_job_id' => $jobId,
                'line_number' => 1,
                'line_source_type' => 'labor',
                'description' => 'Brake service',
                'quantity' => '1.0000',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('vehicle_service_line_employees')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'vehicle_service_job_id' => $jobId,
                'vehicle_service_job_line_id' => $lineId,
                'employee_id' => $technician->id,
                'role_type' => 'technician',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $service = app(EmployeeQueryService::class);

            $this->expectException(ConflictHttpException::class);
            $this->expectExceptionMessage('Employee cannot be deleted while assigned to service job lines.');

            $service->delete($technician, (int) $technician->row_version);
        });
    }

    public function test_unreferenced_employee_can_be_deleted(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Office Branch',
                'code' => 'OFF-'.Str::upper(Str::random(4)),
            ]);

            $employee = $this->createEmployee($tenantId, $orgId, 'Solo Worker', 'EMP-SOLO-01');
            $service = app(EmployeeQueryService::class);

            $service->delete($employee, (int) $employee->row_version);

            $this->assertSoftDeleted('hr_employees', ['id' => $employee->id]);
        });
    }

    private function createEmployee(int $tenantId, int $orgId, string $name, string $code, ?int $reportingManagerId = null): HrEmployee
    {
        return HrEmployee::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgId,
            'employee_number' => $code,
            'code' => $code,
            'first_name' => $name,
            'display_name' => $name,
            'reporting_manager_id' => $reportingManagerId,
            'status' => 'active',
            'row_version' => 1,
        ]);
    }

    private function tenant(): int
    {
        $now = now();
        $code = Str::upper(Str::random(6));

        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'HR Test Tenant '.$code,
            'slug' => 'hr-test-tenant-'.strtolower($code),
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Testing HR deletion blockers',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        \Tests\Support\ActiveTenantSubscriptionFixture::create($tenantId);

        return $tenantId;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\HR;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class HrCrudEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_hr_employee_crud_works_without_automatic_user_access(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();

        $departmentId = (int) $this->withHeaders($headers)
            ->postJson('/api/hr/departments', [
                'department_code' => 'WORKSHOP',
                'department_name' => 'Workshop Operations',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.department_code', 'WORKSHOP')
            ->json('data.id');

        $designationId = (int) $this->withHeaders($headers)
            ->postJson('/api/hr/designations', [
                'department_id' => $departmentId,
                'designation_code' => 'TECH',
                'designation_name' => 'Technician',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.designation_code', 'TECH')
            ->json('data.id');

        $employmentTypeId = (int) $this->withHeaders($headers)
            ->postJson('/api/hr/employment-types', [
                'employment_type_code' => 'FULL_TIME',
                'employment_type_name' => 'Full Time',
                'description' => 'Permanent full-time staff',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.employment_type_code', 'FULL_TIME')
            ->json('data.id');

        $createResponse = $this->withHeaders($headers)->postJson('/api/hr/employees', [
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'employee_code' => 'EMP-TST-001',
            'employment_status' => 'draft',
            'employment_type_id' => $employmentTypeId,
            'first_name' => 'Nimal',
            'joining_date' => '2026-05-01',
            'last_name' => 'Perera',
            'mobile' => '+94770000010',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.employee_code', 'EMP-TST-001')
            ->assertJsonPath('data.first_name', 'Nimal');

        $employeeId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('employees', [
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'employee_code' => 'EMP-TST-001',
            'id' => $employeeId,
            'organization_unit_id' => $organizationUnitId,
            'tenant_id' => $tenantId,
        ]);
        $this->assertDatabaseMissing('employee_user_accounts', ['employee_id' => $employeeId]);

        $this->withHeaders($headers)
            ->getJson('/api/hr/employees?search=Nimal')
            ->assertOk()
            ->assertJsonPath('data.0.id', $employeeId);

        $this->withHeaders($headers)
            ->getJson('/api/hr/employees/'.$employeeId)
            ->assertOk()
            ->assertJsonPath('data.id', $employeeId);

        $this->withHeaders($headers)
            ->putJson('/api/hr/employees/'.$employeeId, [
                'department_id' => $departmentId,
                'designation_id' => $designationId,
                'employee_code' => 'EMP-TST-001',
                'employment_status' => 'draft',
                'employment_type_id' => $employmentTypeId,
                'first_name' => 'Nimal',
                'last_name' => 'Fernando',
            ])
            ->assertOk()
            ->assertJsonPath('data.last_name', 'Fernando');

        $this->withHeaders($headers)
            ->patchJson('/api/hr/employees/'.$employeeId.'/status', ['employment_status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.employment_status', 'active');

        $this->assertDatabaseHas('employee_status_histories', [
            'employee_id' => $employeeId,
            'tenant_id' => $tenantId,
            'to_status' => 'active',
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/hr/employees/'.$employeeId.'/contacts', [
                'contact_name' => 'Kamal Perera',
                'contact_type' => 'emergency',
                'is_emergency' => true,
                'phone' => '+94770000011',
                'relationship' => 'Brother',
            ])
            ->assertCreated()
            ->assertJsonPath('data.contact_name', 'Kamal Perera');

        $this->withHeaders($headers)
            ->getJson('/api/hr/employees/'.$employeeId.'/contacts')
            ->assertOk()
            ->assertJsonPath('data.0.contact_name', 'Kamal Perera');

        $this->withHeaders($headers)
            ->postJson('/api/hr/employees/'.$employeeId.'/addresses', [
                'address_line_1' => 'No 20, Lake Road',
                'address_type' => 'current',
                'city' => 'Colombo',
                'country_name' => 'Sri Lanka',
                'is_primary' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.address_line_1', 'No 20, Lake Road');

        $this->withHeaders($headers)
            ->getJson('/api/hr/employees/'.$employeeId.'/addresses')
            ->assertOk()
            ->assertJsonPath('data.0.address_line_1', 'No 20, Lake Road');

        $this->withHeaders($headers)
            ->putJson('/api/hr/employees/'.$employeeId.'/employment-details', [
                'department_id' => $departmentId,
                'designation_id' => $designationId,
                'employment_status' => 'active',
                'employment_type_id' => $employmentTypeId,
                'joining_date' => '2026-05-01',
            ])
            ->assertOk()
            ->assertJsonPath('data.department_id', $departmentId);

        $this->withHeaders($headers)
            ->getJson('/api/hr/employees/active')
            ->assertOk()
            ->assertJsonPath('data.0.id', $employeeId);

        $this->withHeaders($headers)
            ->getJson('/api/hr/employees/'.$employeeId.'/validate/assignment-context/vehicle-service-technician')
            ->assertOk()
            ->assertJsonPath('data.is_assignable', true);
    }

    public function test_hr_employee_create_returns_validation_errors(): void
    {
        [, , $headers] = $this->authenticatedHeaders();

        $this->withHeaders($headers)
            ->postJson('/api/hr/employees', ['employee_code' => 'EMP-TST-VAL'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name']);
    }

    /**
     * @return array{0:int,1:int,2:array<string,string>}
     */
    private function authenticatedHeaders(): array
    {
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
        $organizationUnitId = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', 'MAIN')
            ->value('id');

        $loginResponse = $this->postJson('/api/auth/login', [
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'provider_key' => 'internal',
            'tenant_id' => $tenantId,
        ]);

        $loginResponse->assertOk();

        return [
            $tenantId,
            $organizationUnitId,
            [
                'Authorization' => 'Bearer '.(string) $loginResponse->json('data.tokens.access_token'),
                'X-Organization-Unit-ID' => (string) $organizationUnitId,
                'X-Tenant-ID' => (string) $tenantId,
            ],
        ];
    }
}

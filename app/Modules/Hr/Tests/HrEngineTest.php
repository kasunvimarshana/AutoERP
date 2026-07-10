<?php

declare(strict_types=1);

namespace Modules\Hr\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Hr\DTOs\CreateEmployeeData;
use Modules\Hr\DTOs\EmployeeRateData;
use Modules\Hr\DTOs\EmployeeStatusChangeData;
use Modules\Hr\DTOs\HrDepartmentData;
use Modules\Hr\DTOs\HrDesignationData;
use Modules\Hr\DTOs\HrEmploymentTypeData;
use Modules\Hr\DTOs\HrSkillData;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeRateType;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Services\EmployeeCreationService;
use Modules\Hr\Services\EmployeeLookupService;
use Modules\Hr\Services\EmployeeRateService;
use Modules\Hr\Services\EmployeeStatusService;
use Modules\Hr\Services\HrDepartmentService;
use Modules\Hr\Services\HrDesignationService;
use Modules\Hr\Services\HrEmploymentTypeService;
use Modules\Hr\Services\HrSkillService;
use Modules\User\Models\UserModel;
use Tests\TestCase;

final class HrEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trustTenantScopedRequestContextFromPayload();
    }

    public function test_employee_creation_status_history_and_lookup(): void
    {
        [$tenantId, $organizationId, $currencyId] = $this->scope();
        [$department, $designation, $employmentType, $skill] = $this->masters($tenantId, $organizationId);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationId, $currencyId, $department, $designation, $employmentType, $skill): void {
            $employee = app(EmployeeCreationService::class)->create(new CreateEmployeeData(
                tenantId: $tenantId,
                organizationUnitId: $organizationId,
                employeeNumber: 'EMP-TEST-1',
                firstName: 'Nimal',
                displayName: 'Nimal Perera',
                departmentId: (int) $department->getKey(),
                designationId: (int) $designation->getKey(),
                employmentTypeId: (int) $employmentType->getKey(),
                status: EmployeeStatus::Active,
                availabilityStatus: EmployeeAvailabilityStatus::Available,
                defaultHourlyRate: '25.500000',
            ));

            app(EmployeeRateService::class)->create($employee, new EmployeeRateData(EmployeeRateType::Service, '40.000000', $currencyId, '2026-01-01'));
            app(EmployeeStatusService::class)->change($employee, new EmployeeStatusChangeData(EmployeeStatus::OnLeave, (int) $employee->row_version, 'Annual leave', null));

            $this->assertSame(EmployeeStatus::OnLeave, $employee->refresh()->status);
            $this->assertSame(EmployeeAvailabilityStatus::OnLeave, $employee->availability_status);
            $this->assertSame(2, (int) $employee->row_version);
            $this->assertCount(2, $employee->statusHistories);
            $this->assertSame('25.500000', (string) $employee->default_hourly_rate);
            $this->assertTrue(app(EmployeeLookupService::class)->employeesByDepartment($tenantId, (int) $department->getKey(), $organizationId)->contains($employee));
            $this->assertFalse(app(EmployeeLookupService::class)->employeesAvailableForVehicleService($tenantId, $organizationId, (int) $skill->getKey())->contains($employee));
        });
    }

    public function test_rate_overlap_and_cross_tenant_reference_are_rejected(): void
    {
        [$tenantId, $organizationId, $currencyId] = $this->scope();
        [$otherTenantId, $otherOrganizationId] = $this->scope();
        [$department] = $this->masters($tenantId, $organizationId);
        [$otherDepartment] = $this->masters($otherTenantId, $otherOrganizationId);
        $employee = $this->employee($tenantId, $organizationId, 'EMP-RATE', (int) $department->getKey());
        $rates = app(EmployeeRateService::class);
        $this->withTenantExecutionContext($tenantId, fn () => $rates->create($employee, new EmployeeRateData(EmployeeRateType::Hourly, '10.000000', $currencyId, '2026-01-01', '2026-12-31')));

        try {
            $this->withTenantExecutionContext($tenantId, fn () => $rates->create($employee, new EmployeeRateData(EmployeeRateType::Hourly, '12.000000', $currencyId, '2026-06-01', '2027-01-01')));
            $this->fail('Expected overlapping rate to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Active employee rates of the same type cannot overlap.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HR reference belongs to a different tenant.');
        $this->employee($tenantId, $organizationId, 'EMP-CROSS', (int) $otherDepartment->getKey());
    }

    public function test_one_shot_api_creates_readable_employee_graph_and_relations(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationId, $currencyId] = $this->scope();
        [$department, $designation, $employmentType, $skill] = $this->masters($tenantId, $organizationId);
        $this->actingAsHrUser($tenantId);

        $response = $this->tenantPostJson($tenantId, '/api/v1/hr/employees/with-relations', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationId,
            'employee' => [
                'employee_number' => 'EMP-API-1',
                'first_name' => 'Kasun',
                'last_name' => 'Silva',
                'display_name' => 'Kasun Silva',
                'department_id' => $department->getKey(),
                'designation_id' => $designation->getKey(),
                'employment_type_id' => $employmentType->getKey(),
                'status' => 'active',
                'availability_status' => 'available',
            ],
            'contacts' => [['contact_name' => 'Emergency Contact', 'relationship' => 'Parent', 'is_primary' => true]],
            'addresses' => [['address_type' => 'current', 'address_line_1' => '10 Main Street', 'is_primary' => true]],
            'documents' => [['document_type' => 'contract', 'document_number' => 'CON-1', 'status' => 'active']],
            'skills' => [['skill_id' => $skill->getKey(), 'proficiency_level' => 'advanced', 'years_of_experience' => '4.500000']],
            'rates' => [['rate_type' => 'service', 'amount' => '50.000000', 'currency_id' => $currencyId, 'effective_from' => '2026-01-01']],
            'availability' => ['availability_status' => 'available', 'availability_date' => '2026-06-07'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.employee_number', 'EMP-API-1')
            ->assertJsonPath('data.row_version', 1)
            ->assertJsonPath('data.department.name', $department->name)
            ->assertJsonPath('data.skills.0.skill.name', $skill->name)
            ->assertJsonPath('data.rates.0.amount', '50.000000')
            ->assertJsonStructure(['data' => ['id', 'row_version', 'name', 'employee_number', 'department', 'designation', 'contacts', 'addresses', 'documents', 'skills', 'rates', 'availability', 'status_history']]);

        $id = (int) $response->json('data.id');
        $this->tenantGetJson($tenantId, "/api/v1/hr/employees/lookup/service-available?tenant_id={$tenantId}&organization_unit_id={$organizationId}&skill_id={$skill->getKey()}")
            ->assertOk()->assertJsonFragment(['employee_number' => 'EMP-API-1']);
        $this->tenantGetJson($tenantId, "/api/v1/hr/employees/{$id}/contacts?tenant_id={$tenantId}&organization_unit_id={$organizationId}")
            ->assertOk()->assertJsonPath('data.0.contact_name', 'Emergency Contact');
    }

    public function test_master_crud_relation_crud_status_and_validation_api(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationId] = $this->scope();
        $this->actingAsHrUser($tenantId);
        $department = $this->tenantPostJson($tenantId, '/api/v1/hr/departments', ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationId, 'code' => 'API-DEP', 'name' => 'API Department'])
            ->assertCreated()->json('data');
        $this->tenantGetJson($tenantId, "/api/v1/hr/departments/lookup?tenant_id={$tenantId}&organization_unit_id={$organizationId}")
            ->assertOk()->assertJsonFragment(['name' => 'API Department']);

        $employee = $this->tenantPostJson($tenantId, '/api/v1/hr/employees', ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationId, 'employee_number' => 'EMP-CRUD', 'first_name' => 'Amal', 'display_name' => 'Amal', 'department_id' => $department['id'], 'status' => 'pending_approval'])
            ->assertCreated()->json('data');
        $id = (int) $employee['id'];
        $contact = $this->tenantPostJson($tenantId, "/api/v1/hr/employees/{$id}/contacts", ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationId, 'contact_name' => 'Primary', 'is_primary' => true])
            ->assertCreated()->json('data');
        $this->withTenantExecutionContext($tenantId, fn () => $this->putJson("/api/v1/hr/employees/{$id}/contacts/{$contact['id']}", ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationId, 'contact_name' => 'Updated Primary', 'is_primary' => true]))
            ->assertOk()->assertJsonPath('data.contact_name', 'Updated Primary');
        $employee = $this->tenantPatchJson($tenantId, "/api/v1/hr/employees/{$id}/status", ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationId, 'row_version' => $employee['row_version'], 'status' => 'active', 'reason' => 'Approved'])
            ->assertOk()->assertJsonPath('data.status', 'active')->json('data');
        $this->withTenantExecutionContext($tenantId, fn () => $this->putJson("/api/v1/hr/employees/{$id}", ['tenant_id' => $tenantId, 'organization_unit_id' => $organizationId, 'row_version' => $employee['row_version'], 'mobile' => '0771234567']))
            ->assertOk()->assertJsonPath('data.mobile', '0771234567')->assertJsonPath('data.row_version', $employee['row_version'] + 1);
        $this->tenantGetJson($tenantId, "/api/v1/hr/employees/{$id}/status-history?tenant_id={$tenantId}&organization_unit_id={$organizationId}")
            ->assertOk()->assertJsonCount(2, 'data');
        $this->tenantPostJson($tenantId, '/api/v1/hr/employees', ['tenant_id' => $tenantId, 'first_name' => '', 'status' => 'invalid', 'default_hourly_rate' => '-1'])
            ->assertUnprocessable()->assertJsonValidationErrors(['first_name', 'status', 'default_hourly_rate']);
    }

    public function test_employee_mutations_reject_stale_row_versions(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationId] = $this->scope();
        [$department] = $this->masters($tenantId, $organizationId);
        $this->actingAsHrUser($tenantId);

        $employee = $this->tenantPostJson($tenantId, '/api/v1/hr/employees', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationId,
            'employee_number' => 'EMP-VERSION',
            'first_name' => 'Versioned',
            'display_name' => 'Versioned Employee',
            'department_id' => $department->getKey(),
            'status' => 'pending_approval',
        ])->assertCreated()->json('data');

        $this->tenantPatchJson($tenantId, "/api/v1/hr/employees/{$employee['id']}/status", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationId,
            'row_version' => $employee['row_version'],
            'status' => 'active',
        ])->assertOk()->assertJsonPath('data.row_version', $employee['row_version'] + 1);

        $this->withTenantExecutionContext($tenantId, fn () => $this->putJson("/api/v1/hr/employees/{$employee['id']}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationId,
            'row_version' => $employee['row_version'],
            'mobile' => '0770000000',
        ]))->assertConflict();
    }

    public function test_all_master_and_assignment_endpoints(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationId, $currencyId] = $this->scope();
        $this->actingAsHrUser($tenantId);
        $masters = [];
        foreach ([
            'departments' => 'DEP', 'designations' => 'DES', 'employment-types' => 'TYPE',
            'skills' => 'SKILL', 'certifications' => 'CERT', 'licenses' => 'LIC',
        ] as $endpoint => $code) {
            $masters[$endpoint] = $this->tenantPostJson($tenantId, "/api/v1/hr/{$endpoint}", [
                'tenant_id' => $tenantId, 'organization_unit_id' => $organizationId,
                'code' => $code, 'name' => "Master {$code}",
            ])->assertCreated()->assertJsonPath('data.name', "Master {$code}")->json('data');
        }

        $employee = $this->tenantPostJson($tenantId, '/api/v1/hr/employees', [
            'tenant_id' => $tenantId, 'organization_unit_id' => $organizationId,
            'employee_number' => 'EMP-RELATIONS', 'first_name' => 'Relation', 'display_name' => 'Relation Employee',
            'department_id' => $masters['departments']['id'], 'designation_id' => $masters['designations']['id'],
            'employment_type_id' => $masters['employment-types']['id'], 'status' => 'active',
        ])->assertCreated()->json('data');
        $id = (int) $employee['id'];

        $relations = [
            'addresses' => ['address_type' => 'work', 'address_line_1' => 'Workshop'],
            'documents' => ['document_type' => 'contract', 'document_number' => 'DOC-REL'],
            'skills' => ['skill_id' => $masters['skills']['id'], 'proficiency_level' => 'expert'],
            'certifications' => ['certification_id' => $masters['certifications']['id'], 'certificate_number' => 'CERT-REL'],
            'licenses' => ['license_id' => $masters['licenses']['id'], 'license_number' => 'LIC-REL'],
            'rates' => ['rate_type' => 'hourly', 'amount' => '30.000000', 'currency_id' => $currencyId],
        ];
        foreach ($relations as $endpoint => $payload) {
            $this->tenantPostJson($tenantId, "/api/v1/hr/employees/{$id}/{$endpoint}", [
                'tenant_id' => $tenantId, 'organization_unit_id' => $organizationId, ...$payload,
            ])->assertCreated();
            $this->tenantGetJson($tenantId, "/api/v1/hr/employees/{$id}/{$endpoint}?tenant_id={$tenantId}&organization_unit_id={$organizationId}")
                ->assertOk()->assertJsonCount(1, 'data');
        }
        $this->tenantPostJson($tenantId, "/api/v1/hr/employees/{$id}/availability", [
            'tenant_id' => $tenantId, 'organization_unit_id' => $organizationId,
            'availability_status' => 'assigned', 'source_type' => 'vehicle_service', 'source_id' => 99,
        ])->assertCreated()->assertJsonPath('data.source_type', 'vehicle_service');
    }

    public function test_tenant_and_organization_isolation_on_reads(): void
    {
        $this->withoutMiddleware();
        [$tenantId, $organizationId] = $this->scope();
        [$otherTenantId, $otherOrganizationId] = $this->scope();
        [$department] = $this->masters($tenantId, $organizationId);
        $employee = $this->employee($tenantId, $organizationId, 'EMP-ISOLATED', (int) $department->getKey());
        $this->actingAsHrUser($otherTenantId);

        $this->tenantGetJson($otherTenantId, "/api/v1/hr/employees/{$employee->getKey()}?tenant_id={$otherTenantId}&organization_unit_id={$otherOrganizationId}")
            ->assertNotFound();
        $this->tenantGetJson($otherTenantId, "/api/v1/hr/employees?tenant_id={$otherTenantId}&organization_unit_id={$otherOrganizationId}")
            ->assertOk()->assertJsonMissing(['employee_number' => 'EMP-ISOLATED']);
    }

    private function employee(int $tenantId, int $organizationId, string $number, int $departmentId): HrEmployee
    {
        return $this->withTenantExecutionContext($tenantId, fn (): HrEmployee => app(EmployeeCreationService::class)->create(new CreateEmployeeData(tenantId: $tenantId, organizationUnitId: $organizationId, employeeNumber: $number, firstName: $number, displayName: $number, departmentId: $departmentId, status: EmployeeStatus::Active)));
    }

    private function masters(int $tenantId, int $organizationId): array
    {
        $suffix = Str::upper(Str::random(5));
        return $this->withTenantExecutionContext($tenantId, fn (): array => [
            app(HrDepartmentService::class)->create(new HrDepartmentData($tenantId, "DEP-{$suffix}", 'Service', $organizationId)),
            app(HrDesignationService::class)->create(new HrDesignationData($tenantId, "DES-{$suffix}", 'Technician', $organizationId)),
            app(HrEmploymentTypeService::class)->create(new HrEmploymentTypeData($tenantId, "TYPE-{$suffix}", 'Full Time', $organizationId)),
            app(HrSkillService::class)->create(new HrSkillData($tenantId, "SKILL-{$suffix}", 'Diagnostics', $organizationId)),
        ]);
    }

    private function actingAsHrUser(int $tenantId): void
    {
        $userId = \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'email' => 'hr-user-'.Str::lower(Str::random(8)).'@example.test',
        ]);

        $user = $this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        );

        $this->actingAs($user, (string) config('module-auth.protected_route_guard', 'auth-api'));
    }

    private function scope(): array
    {
        $suffix = Str::upper(Str::random(8));
        $currencyId = (int) DB::table('currencies')->insertGetId(['row_version' => 1, 'code' => 'H'.substr($suffix, 0, 4), 'name' => "HR Currency {$suffix}", 'symbol' => 'H', 'decimal_places' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $tenantId = (int) DB::table('tenants')->insertGetId(['uuid' => (string) Str::uuid(), 'code' => "TEN-HR-{$suffix}", 'name' => "HR Tenant {$suffix}", 'slug' => 'hr-'.Str::lower($suffix), 'status' => 'active', 'status_changed_at' => now(), 'base_currency_id' => $currencyId, 'created_at' => now(), 'updated_at' => now()]);
        $organizationId = (int) \Tests\Support\OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => "HR Org {$suffix}", 'code' => "ORG-{$suffix}", 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        return [$tenantId, $organizationId, $currencyId];
    }
}

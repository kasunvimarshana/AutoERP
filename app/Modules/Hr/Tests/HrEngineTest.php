<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Hr\DTOs\AttendanceRegularizationData;
use Modules\Hr\DTOs\CompOffClaimData;
use Modules\Hr\DTOs\CreateEmployeeData;
use Modules\Hr\DTOs\EmployeeRateData;
use Modules\Hr\DTOs\EmployeeStatusChangeData;
use Modules\Hr\DTOs\HrDepartmentData;
use Modules\Hr\DTOs\HrDesignationData;
use Modules\Hr\DTOs\HrEmploymentTypeData;
use Modules\Hr\DTOs\HrSkillData;
use Modules\Hr\DTOs\LeaveRequestData;
use Modules\Hr\DTOs\LeaveTypeData;
use Modules\Hr\DTOs\PunchData;
use Modules\Hr\DTOs\ShiftAssignmentData;
use Modules\Hr\DTOs\ShiftData;
use Modules\Hr\DTOs\ShiftReallocationData;
use Modules\Hr\DTOs\ShiftSwapData;
use Modules\Hr\Enums\AttendanceLockStatus;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\CompOffStatus;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeRateType;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Enums\RegularizationStatus;
use Modules\Hr\Enums\RegularizationType;
use Modules\Hr\Enums\ShiftAssignmentType;
use Modules\Hr\Enums\ShiftSwapStatus;
use Modules\Hr\Models\HrAttendanceAdjustment;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrLeaveBalance;
use Modules\Hr\Services\AttendanceCalculationEngine;
use Modules\Hr\Services\AttendanceRegularizationService;
use Modules\Hr\Services\EmployeeCreationService;
use Modules\Hr\Services\EmployeeLookupService;
use Modules\Hr\Services\EmployeeRateService;
use Modules\Hr\Services\EmployeeStatusService;
use Modules\Hr\Services\HrDepartmentService;
use Modules\Hr\Services\HrDesignationService;
use Modules\Hr\Services\HrEmploymentTypeService;
use Modules\Hr\Services\HrSkillService;
use Modules\Hr\Services\LeaveManagementService;
use Modules\Hr\Services\ShiftRosterService;
use Modules\User\Models\UserModel;
use Tests\Support\CurrencyFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
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
        $this->tenantGetJson($tenantId, "/api/v1/hr/employees/lookup/available?tenant_id={$tenantId}&organization_unit_id={$organizationId}&designation_code={$designation->code}")
            ->assertOk()->assertJsonFragment(['employee_number' => 'EMP-API-1']);
        $this->tenantGetJson($tenantId, "/api/v1/hr/employees/lookup/available?tenant_id={$tenantId}&organization_unit_id={$organizationId}&exclude_designation_code={$designation->code}")
            ->assertOk()->assertJsonMissing(['employee_number' => 'EMP-API-1']);
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

    public function test_shifts_roster_resolution_overrides_and_swaps(): void
    {
        [$tenantId, $organizationId] = $this->scope();
        [$department] = $this->masters($tenantId, $organizationId);
        $rosterService = app(ShiftRosterService::class);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationId, $department, $rosterService): void {
            $dayShift = $rosterService->createShift($tenantId, new ShiftData(
                code: 'DAY-1',
                name: 'Morning Day Shift',
                startTime: '08:00:00',
                endTime: '17:00:00',
                organizationUnitId: $organizationId,
            ));

            $nightShift = $rosterService->createShift($tenantId, new ShiftData(
                code: 'NIGHT-1',
                name: 'Overnight Shift',
                startTime: '22:00:00',
                endTime: '06:00:00',
                isOvernight: true,
                organizationUnitId: $organizationId,
            ));

            $empA = $this->employee($tenantId, $organizationId, 'EMP-ROSTER-A', (int) $department->getKey());
            $empB = $this->employee($tenantId, $organizationId, 'EMP-ROSTER-B', (int) $department->getKey());
            $empC = $this->employee($tenantId, $organizationId, 'EMP-ROSTER-C', (int) $department->getKey());

            // 1. Recurring weekly assignment for Emp A (1 = Monday)
            $rosterService->assignShift($tenantId, new ShiftAssignmentData(
                employeeId: (int) $empA->getKey(),
                shiftId: (int) $dayShift->getKey(),
                assignmentType: ShiftAssignmentType::Recurring,
                dayOfWeek: 1,
            ));

            // 2026-11-02 is Monday -> Day Shift
            $resolvedMon = $rosterService->resolveShift($tenantId, (int) $empA->getKey(), '2026-11-02');
            $this->assertTrue($resolvedMon->isWorkingDay);
            $this->assertSame('DAY-1', $resolvedMon->shift?->code);
            $this->assertSame('recurring', $resolvedMon->resolutionSource);

            // 2. Specific Date Override for Emp A on Tuesday 2026-11-03 -> Night Shift
            $rosterService->assignShift($tenantId, new ShiftAssignmentData(
                employeeId: (int) $empA->getKey(),
                shiftId: (int) $nightShift->getKey(),
                assignmentType: ShiftAssignmentType::SpecificDate,
                startDate: '2026-11-03',
            ));

            $resolvedTue = $rosterService->resolveShift($tenantId, (int) $empA->getKey(), '2026-11-03');
            $this->assertTrue($resolvedTue->isWorkingDay);
            $this->assertSame('NIGHT-1', $resolvedTue->shift?->code);
            $this->assertSame('specific_date', $resolvedTue->resolutionSource);

            // 3. Shift Swap between A and B
            $swap = $rosterService->requestSwap($tenantId, new ShiftSwapData(
                requesterEmployeeId: (int) $empA->getKey(),
                targetEmployeeId: (int) $empB->getKey(),
                swapDate: '2026-11-04',
                targetSwapDate: '2026-11-05',
                reason: 'Doctor appointment swap',
                targetShiftId: (int) $nightShift->getKey(),
            ));
            $this->assertSame(ShiftSwapStatus::PendingPeer, $swap->status);

            // Peer accepts
            $swap = $rosterService->peerActionSwap($swap, (int) $empB->getKey(), true);
            $this->assertSame(ShiftSwapStatus::PeerAccepted, $swap->status);

            // Manager approves
            $managerId = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'manager-'.Str::lower(Str::random(8)).'@example.test',
            ]);
            $swap = $rosterService->managerActionSwap($swap, $managerId, true, 'Approved by Line Manager');
            $this->assertSame(ShiftSwapStatus::ManagerApproved, $swap->status);

            $resolvedSwap = $rosterService->resolveShift($tenantId, (int) $empA->getKey(), '2026-11-04');
            $this->assertSame('swap', $resolvedSwap->resolutionSource);
            $this->assertSame('NIGHT-1', $resolvedSwap->shift?->code);

            // 4. Absent Staff Cover Reallocation
            $realloc = $rosterService->reallocateCover($tenantId, new ShiftReallocationData(
                absentEmployeeId: (int) $empA->getKey(),
                coverEmployeeId: (int) $empC->getKey(),
                shiftId: (int) $dayShift->getKey(),
                reallocationDate: '2026-11-06',
            ));
            $this->assertSame((int) $dayShift->getKey(), (int) $realloc->shift_id);

            $resolvedCover = $rosterService->resolveShift($tenantId, (int) $empC->getKey(), '2026-11-06');
            $this->assertSame('reallocation', $resolvedCover->resolutionSource);
            $this->assertSame('DAY-1', $resolvedCover->shift?->code);
        });
    }

    public function test_attendance_clocking_multi_breaks_and_overtime_calculation(): void
    {
        [$tenantId, $organizationId] = $this->scope();
        [$department] = $this->masters($tenantId, $organizationId);
        $rosterService = app(ShiftRosterService::class);
        $attendanceEngine = app(AttendanceCalculationEngine::class);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationId, $department, $rosterService, $attendanceEngine): void {
            $shift = $rosterService->createShift($tenantId, new ShiftData(
                code: 'REG-8H',
                name: 'Standard 8 Hour Shift',
                startTime: '09:00:00',
                endTime: '17:00:00',
                graceInMinutes: 15,
                graceOutMinutes: 15,
                halfDayThresholdMinutes: 240,
                fullDayThresholdMinutes: 480,
                isBreakPaid: false,
                organizationUnitId: $organizationId,
            ));

            $emp = $this->employee($tenantId, $organizationId, 'EMP-ATT-1', (int) $department->getKey());
            $empId = (int) $emp->getKey();

            // Assign shift for 2026-11-16 (Monday)
            $rosterService->assignShift($tenantId, new ShiftAssignmentData(
                employeeId: $empId,
                shiftId: (int) $shift->getKey(),
                assignmentType: ShiftAssignmentType::SpecificDate,
                startDate: '2026-11-16',
            ));

            // Clock in at 09:10 (within 15 min grace -> late = 0)
            $punchIn = $attendanceEngine->recordPunch($tenantId, new PunchData(
                employeeId: $empId,
                punchType: PunchType::In,
                punchTime: '2026-11-16 09:10:00',
            ));
            $this->assertFalse($punchIn->is_ignored);

            // Duplicate punch within 120s -> ignored
            $dupPunch = $attendanceEngine->recordPunch($tenantId, new PunchData(
                employeeId: $empId,
                punchType: PunchType::In,
                punchTime: '2026-11-16 09:11:00',
            ));
            $this->assertTrue($dupPunch->is_ignored);

            // Break out at 12:00, Break in at 13:00 (60 min unpaid break)
            $attendanceEngine->recordPunch($tenantId, new PunchData(
                employeeId: $empId,
                punchType: PunchType::BreakOut,
                punchTime: '2026-11-16 12:00:00',
            ));
            $attendanceEngine->recordPunch($tenantId, new PunchData(
                employeeId: $empId,
                punchType: PunchType::BreakIn,
                punchTime: '2026-11-16 13:00:00',
            ));

            // Clock out at 18:10 (Total elapsed 9h = 540m; Unpaid break 60m; Total worked = 480m = 8h. Overtime = 0)
            $attendanceEngine->recordPunch($tenantId, new PunchData(
                employeeId: $empId,
                punchType: PunchType::Out,
                punchTime: '2026-11-16 18:10:00',
            ));

            $record = HrAttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $empId)
                ->whereDate('attendance_date', '2026-11-16')
                ->firstOrFail();

            $this->assertSame(480, (int) $record->scheduled_minutes);
            $this->assertSame(480, (int) $record->total_worked_minutes);
            $this->assertSame(60, (int) $record->total_break_minutes);
            $this->assertSame(0, (int) $record->late_arrival_minutes);
            $this->assertSame(0, (int) $record->overtime_minutes);
            $this->assertSame(AttendanceStatus::Present, $record->status);

            // Test Overnight Shift Mapping across midnight
            $nightShift = $rosterService->createShift($tenantId, new ShiftData(
                code: 'NIGHT-MAP',
                name: 'Overnight Mapping Shift',
                startTime: '22:00:00',
                endTime: '06:00:00',
                isOvernight: true,
                organizationUnitId: $organizationId,
            ));
            $rosterService->assignShift($tenantId, new ShiftAssignmentData(
                employeeId: $empId,
                shiftId: (int) $nightShift->getKey(),
                assignmentType: ShiftAssignmentType::SpecificDate,
                startDate: '2026-11-17',
            ));

            // Punch Out early morning Nov 18 at 06:00 maps to Nov 17 operational date!
            $nightOutPunch = $attendanceEngine->recordPunch($tenantId, new PunchData(
                employeeId: $empId,
                punchType: PunchType::Out,
                punchTime: '2026-11-18 06:00:00',
            ));
            $this->assertSame('2026-11-17', $nightOutPunch->attendance_date->toDateString());
        });
    }

    public function test_attendance_regularization_workflow_and_immutable_audit_adjustments(): void
    {
        [$tenantId, $organizationId] = $this->scope();
        [$department] = $this->masters($tenantId, $organizationId);
        $rosterService = app(ShiftRosterService::class);
        $attendanceEngine = app(AttendanceCalculationEngine::class);
        $regularizationService = app(AttendanceRegularizationService::class);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationId, $department, $rosterService, $attendanceEngine, $regularizationService): void {
            $shift = $rosterService->createShift($tenantId, new ShiftData(
                code: 'REG-FIX',
                name: 'Shift for Regularization Fix',
                startTime: '09:00:00',
                endTime: '17:00:00',
                organizationUnitId: $organizationId,
            ));

            $emp = $this->employee($tenantId, $organizationId, 'EMP-REG-1', (int) $department->getKey());
            $empId = (int) $emp->getKey();

            $rosterService->assignShift($tenantId, new ShiftAssignmentData(
                employeeId: $empId,
                shiftId: (int) $shift->getKey(),
                assignmentType: ShiftAssignmentType::SpecificDate,
                startDate: '2026-11-19',
            ));

            // Employee only punches In, forgot to punch Out
            $attendanceEngine->recordPunch($tenantId, new PunchData(
                employeeId: $empId,
                punchType: PunchType::In,
                punchTime: '2026-11-19 09:00:00',
            ));

            $record = HrAttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $empId)
                ->whereDate('attendance_date', '2026-11-19')
                ->firstOrFail();

            $this->assertSame(AttendanceStatus::Unresolved, $record->status);

            // Employee requests regularization for missing out punch
            $reg = $regularizationService->requestRegularization($tenantId, new AttendanceRegularizationData(
                employeeId: $empId,
                attendanceDate: '2026-11-19',
                regularizationType: RegularizationType::MissingOut,
                employeeNotes: 'Forgot to swipe badge when leaving at 5pm',
                proposedClockOut: '2026-11-19 17:00:00',
                attendanceRecordId: (int) $record->getKey(),
            ));
            $this->assertSame(RegularizationStatus::Pending, $reg->status);

            // Manager approves regularization
            $managerId = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'manager-'.Str::lower(Str::random(8)).'@example.test',
            ]);
            $reg = $regularizationService->actionRegularization($reg, $managerId, true, 'Badge verified by manager');
            $this->assertSame(RegularizationStatus::Approved, $reg->status);

            $record->refresh();
            $this->assertSame(AttendanceStatus::Present, $record->status);
            $this->assertSame(480, (int) $record->total_worked_minutes);
            $this->assertTrue($record->is_regularized);

            // Verify immutable audit adjustment record exists
            $adjustment = HrAttendanceAdjustment::query()
                ->where('attendance_record_id', $record->getKey())
                ->firstOrFail();

            $this->assertSame(AttendanceStatus::Unresolved, $adjustment->previous_status);
            $this->assertSame(AttendanceStatus::Present, $adjustment->new_status);
            $this->assertSame(480, (int) $adjustment->new_worked_minutes);

            // Guardrail 1: Finalized attendance record cannot be updated directly
            $record->update(['lock_status' => AttendanceLockStatus::Finalized]);
            try {
                $record->update(['total_worked_minutes' => 600]);
                $this->fail('Expected updating finalized attendance record to throw LogicException');
            } catch (\LogicException $e) {
                $this->assertStringContainsString('Finalized attendance records cannot be updated directly', $e->getMessage());
            }

            // Guardrail 2: Attendance adjustments are immutable and cannot be updated or deleted
            try {
                $adjustment->update(['new_worked_minutes' => 999]);
                $this->fail('Expected updating attendance adjustment to throw LogicException');
            } catch (\LogicException $e) {
                $this->assertStringContainsString('immutable historical audit records and cannot be updated', $e->getMessage());
            }

            try {
                $adjustment->delete();
                $this->fail('Expected deleting attendance adjustment to throw LogicException');
            } catch (\LogicException $e) {
                $this->assertStringContainsString('immutable historical audit records and cannot be deleted', $e->getMessage());
            }
        });
    }

    public function test_leave_management_balances_and_comp_off_claims(): void
    {
        [$tenantId, $organizationId] = $this->scope();
        [$department] = $this->masters($tenantId, $organizationId);
        $leaveService = app(LeaveManagementService::class);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationId, $department, $leaveService): void {
            $annualLeave = $leaveService->createLeaveType($tenantId, new LeaveTypeData(
                code: 'ANNUAL-TEST',
                name: 'Annual Paid Leave',
                isPaid: true,
                organizationUnitId: $organizationId,
            ));

            $emp = $this->employee($tenantId, $organizationId, 'EMP-LEAVE-1', (int) $department->getKey());
            $empId = (int) $emp->getKey();

            // Initialize balance: 14 days annual allocation
            $balance = $leaveService->getOrCreateBalance($tenantId, $empId, (int) $annualLeave->getKey(), 2026);
            $balance->update([
                'accrued_days' => '14.00',
                'current_balance' => '14.00',
            ]);

            // 1. Employee applies for 2 days leave
            $request = $leaveService->applyLeave($tenantId, new LeaveRequestData(
                employeeId: $empId,
                leaveTypeId: (int) $annualLeave->getKey(),
                startDate: '2026-11-23',
                endDate: '2026-11-24',
                totalDays: '2.00',
                reason: 'Family vacation',
            ));

            $this->assertSame(LeaveRequestStatus::Pending, $request->status);
            $balance->refresh();
            $this->assertSame('2.00', (string) $balance->pending_approval_days);

            // 2. Manager approves leave
            $approverId = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'approver-'.Str::lower(Str::random(8)).'@example.test',
            ]);
            $request = $leaveService->actionLeaveRequest($request, $approverId, true, 'Enjoy your leave');
            $this->assertSame(LeaveRequestStatus::Approved, $request->status);

            $balance->refresh();
            $this->assertSame('0.00', (string) $balance->pending_approval_days);
            $this->assertSame('2.00', (string) $balance->used_days);
            $this->assertSame('12.00', (string) $balance->current_balance);

            // Verify attendance records on those dates are automatically marked as OnLeave
            $attRecord = HrAttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $empId)
                ->whereDate('attendance_date', '2026-11-23')
                ->firstOrFail();
            $this->assertSame(AttendanceStatus::OnLeave, $attRecord->status);

            // 3. Employee cancels leave -> balance restored!
            $request = $leaveService->cancelLeaveRequest($request, $approverId, 'Trip postponed');
            $this->assertSame(LeaveRequestStatus::Cancelled, $request->status);

            $balance->refresh();
            $this->assertSame('0.00', (string) $balance->used_days);
            $this->assertSame('14.00', (string) $balance->current_balance);

            // 4. Comp-off claim and approval
            $claim = $leaveService->claimCompOff($tenantId, new CompOffClaimData(
                employeeId: $empId,
                workedDate: '2026-11-22',
                reason: 'Worked on Sunday emergency production dispatch',
                claimedDays: '1.00',
            ));
            $this->assertSame(CompOffStatus::Pending, $claim->status);

            // Approver approves comp-off
            $claim = $leaveService->actionCompOffClaim($claim, $approverId, true);
            $this->assertSame(CompOffStatus::Approved, $claim->status);

            // Verify Comp-off leave balance was credited
            $compBalance = HrLeaveBalance::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $empId)
                ->whereHas('leaveType', fn ($q) => $q->where('code', 'COMP_OFF'))
                ->firstOrFail();

            $this->assertSame('1.00', (string) $compBalance->current_balance);
        });
    }

    public function test_shift_rotations_overlap_validation_and_leave_conflicts(): void
    {
        [$tenantId, $organizationId] = $this->scope();
        [$department] = $this->masters($tenantId, $organizationId);
        $rosterService = app(ShiftRosterService::class);
        $leaveService = app(LeaveManagementService::class);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $organizationId, $department, $rosterService, $leaveService): void {
            $user = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'roster-tester-'.Str::lower(Str::random(8)).'@example.test',
            ]);

            $emp = $this->employee($tenantId, $organizationId, 'EMP-ROT-1', (int) $department->getKey());
            $empId = (int) $emp->getKey();

            // 1. Create Shifts: Morning (08:00 - 16:00) & Evening (16:00 - 00:00)
            $morningShift = $rosterService->createShift($tenantId, new ShiftData(
                code: 'MORN-1',
                name: 'Morning Shift',
                startTime: '08:00:00',
                endTime: '16:00:00',
                organizationUnitId: $organizationId,
            ));

            $eveningShift = $rosterService->createShift($tenantId, new ShiftData(
                code: 'EVEN-1',
                name: 'Evening Shift',
                startTime: '16:00:00',
                endTime: '00:00:00',
                organizationUnitId: $organizationId,
            ));

            // 2. Create Shift Rotation Pattern (14-day cycle)
            $rotation = $rosterService->createRotation($tenantId, [
                'organization_unit_id' => $organizationId,
                'code' => 'ROT-14D',
                'name' => '14-Day Continental Shift Pattern',
                'cycle_days' => 14,
            ]);

            // Days 1-5: Morning Shift
            for ($d = 1; $d <= 5; $d++) {
                $rosterService->addRotationStep($rotation, $d, (int) $morningShift->getKey(), 'Morning shift');
            }
            // Days 6-7: Rest Days (null shift)
            $rosterService->addRotationStep($rotation, 6, null, 'Rest Day 1');
            $rosterService->addRotationStep($rotation, 7, null, 'Rest Day 2');

            // Days 8-12: Evening Shift
            for ($d = 8; $d <= 12; $d++) {
                $rosterService->addRotationStep($rotation, $d, (int) $eveningShift->getKey(), 'Evening shift');
            }
            // Days 13-14: Rest Days
            $rosterService->addRotationStep($rotation, 13, null, 'Rest Day 3');
            $rosterService->addRotationStep($rotation, 14, null, 'Rest Day 4');

            // Assign rotation starting from anchor date 2026-11-01
            $rosterService->assignRotation($tenantId, $empId, (int) $rotation->getKey(), '2026-11-01');

            // 3. Resolve Shifts across the rotation
            // Day 1 (2026-11-01): Should resolve Morning Shift
            $resolvedDay1 = $rosterService->resolveShift($tenantId, $empId, '2026-11-01');
            $this->assertSame('rotation', $resolvedDay1->resolutionSource);
            $this->assertTrue($resolvedDay1->isWorkingDay);
            $this->assertSame('MORN-1', $resolvedDay1->shift?->code);

            // Day 6 (2026-11-06): Should resolve Rest Day
            $resolvedDay6 = $rosterService->resolveShift($tenantId, $empId, '2026-11-06');
            $this->assertSame('rotation_rest', $resolvedDay6->resolutionSource);
            $this->assertFalse($resolvedDay6->isWorkingDay);
            $this->assertTrue($resolvedDay6->isRestDay);
            $this->assertNull($resolvedDay6->shift);

            // Day 8 (2026-11-08): Should resolve Evening Shift
            $resolvedDay8 = $rosterService->resolveShift($tenantId, $empId, '2026-11-08');
            $this->assertSame('rotation', $resolvedDay8->resolutionSource);
            $this->assertTrue($resolvedDay8->isWorkingDay);
            $this->assertSame('EVEN-1', $resolvedDay8->shift?->code);

            // 4. Overlapping Shift Assignment Validation
            $rosterService->assignShift($tenantId, new ShiftAssignmentData(
                employeeId: $empId,
                shiftId: (int) $morningShift->getKey(),
                assignmentType: ShiftAssignmentType::SpecificDate,
                startDate: '2026-11-20',
                assignedBy: $user,
            ));

            // Assigning another specific-date shift on the same date throws InvalidArgumentException
            try {
                $rosterService->assignShift($tenantId, new ShiftAssignmentData(
                    employeeId: $empId,
                    shiftId: (int) $eveningShift->getKey(),
                    assignmentType: ShiftAssignmentType::SpecificDate,
                    startDate: '2026-11-20',
                    assignedBy: $user,
                ));
                $this->fail('Expected InvalidArgumentException on overlapping specific date shift assignment.');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('already has an active specific-date shift assignment', $e->getMessage());
            }

            // 5. Leave Conflict Validation
            $leaveType = $leaveService->createLeaveType($tenantId, new LeaveTypeData(
                code: 'ANNUAL-CONFLICT',
                name: 'Annual Leave',
                isPaid: true,
                organizationUnitId: $organizationId,
            ));

            $balance = $leaveService->getOrCreateBalance($tenantId, $empId, (int) $leaveType->getKey(), 2026);
            $balance->update(['accrued_days' => '5.00', 'current_balance' => '5.00']);

            $leaveReq = $leaveService->applyLeave($tenantId, new LeaveRequestData(
                employeeId: $empId,
                leaveTypeId: (int) $leaveType->getKey(),
                startDate: '2026-12-05',
                endDate: '2026-12-05',
                totalDays: '1.00',
                reason: 'Holiday',
            ));
            $leaveService->actionLeaveRequest($leaveReq, $user, true);

            // Attempting to assign shift on date of approved leave throws InvalidArgumentException
            try {
                $rosterService->assignShift($tenantId, new ShiftAssignmentData(
                    employeeId: $empId,
                    shiftId: (int) $morningShift->getKey(),
                    assignmentType: ShiftAssignmentType::SpecificDate,
                    startDate: '2026-12-05',
                    assignedBy: $user,
                ));
                $this->fail('Expected InvalidArgumentException when assigning shift on approved leave date.');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Employee has approved leave', $e->getMessage());
            }
        });
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
        $userId = TenantUserFixture::create([
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
        $currencyId = CurrencyFixture::create([
            'name' => "HR Currency {$suffix}",
            'symbol' => 'H',
        ]);
        $tenantId = (int) DB::table('tenants')->insertGetId(['uuid' => (string) Str::uuid(), 'code' => "TEN-HR-{$suffix}", 'name' => "HR Tenant {$suffix}", 'slug' => 'hr-'.Str::lower($suffix), 'status' => 'active', 'status_changed_at' => now(), 'base_currency_id' => $currencyId, 'created_at' => now(), 'updated_at' => now()]);
        $organizationId = (int) OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => "HR Org {$suffix}", 'code' => "ORG-{$suffix}", 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        return [$tenantId, $organizationId, $currencyId];
    }
}

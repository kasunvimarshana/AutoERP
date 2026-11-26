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
use Modules\Hr\Enums\AttendanceLockStatus;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\CompOffStatus;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\ShiftAssignmentType;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrDepartment;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmploymentType;
use Modules\Hr\Models\HrHoliday;
use Modules\Hr\Models\HrHolidayCalendar;
use Modules\Hr\Models\HrLeaveBalance;
use Modules\Hr\Models\HrLeaveType;
use Modules\Hr\Models\HrShift;
use Modules\Hr\Models\HrShiftAssignment;
use Modules\User\Models\UserModel;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class LeaveManagementPhase2Test extends TestCase
{
    use RefreshDatabase;

    public function test_leave_type_soft_delete_unique_code_reuse_and_balance_guard(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $this->actingAs(UserModel::query()->findOrFail($userId));
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-LT']);

            // 1. Create COMP_OFF leave type, soft-delete it, then re-create with the same code
            $createRes = $this->postJson('/api/v1/hr/leave-types', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'COMP_OFF',
                'name' => 'Compensatory Off',
                'is_paid' => true,
                'is_compensatory' => true,
            ])->assertStatus(201);

            $ltId = (int) $createRes->json('data.id');
            $this->deleteJson("/api/v1/hr/leave-types/{$ltId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
            ])->assertStatus(204);

            // Soft-deleted leave type cannot be referenced by policies or leave requests
            $empCheck = $this->createEmployee($tenantId, $orgId, 'Soft Delete Guard Emp', 'EMP-LT-00');
            $this->postJson('/api/v1/hr/leave-policies', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'leave_type_id' => $ltId,
                'name' => 'Orphan Policy',
                'annual_allocation_days' => '10.00',
                'effective_from' => '2026-01-01',
            ])->assertStatus(422);

            $this->postJson('/api/v1/hr/leave-requests', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $empCheck->id,
                'leave_type_id' => $ltId,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-01',
                'reason' => 'Deleted leave type',
            ])->assertStatus(422);

            $recreateRes = $this->postJson('/api/v1/hr/leave-types', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'COMP_OFF',
                'name' => 'Compensatory Off Reborn',
                'is_paid' => true,
                'is_compensatory' => true,
            ])->assertStatus(201);

            $recreatedId = (int) $recreateRes->json('data.id');
            $this->assertNotSame($ltId, $recreatedId);

            // 2. Balance guard: leave type referenced by HrLeaveBalance cannot be deleted
            $employee = $this->createEmployee($tenantId, $orgId, 'Nimal Perera', 'EMP-LT-01');
            HrLeaveBalance::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'leave_type_id' => $recreatedId,
                'calendar_year' => 2026,
                'opening_balance' => '0.00',
                'accrued_days' => '2.00',
                'carried_forward_days' => '0.00',
                'used_days' => '0.00',
                'pending_approval_days' => '0.00',
                'expired_days' => '0.00',
                'current_balance' => '2.00',
            ]);

            $this->deleteJson("/api/v1/hr/leave-types/{$recreatedId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
            ])->assertStatus(422);
        });
    }

    public function test_leave_policy_enforcement_probation_max_consecutive_allocation_and_effective_dates(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $this->actingAs(UserModel::query()->findOrFail($userId));
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-POL']);

            $probationType = HrEmploymentType::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'PROBATION',
                'name' => 'Probationary Staff',
            ]);
            $permanentType = HrEmploymentType::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'PERMANENT',
                'name' => 'Permanent Staff',
            ]);

            $probationEmp = $this->createEmployee($tenantId, $orgId, 'Probation Emp', 'EMP-POL-1', employmentTypeId: (int) $probationType->id);
            $permanentEmp = $this->createEmployee($tenantId, $orgId, 'Permanent Emp', 'EMP-POL-2', employmentTypeId: (int) $permanentType->id);

            $annualType = HrLeaveType::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'ANNUAL-POL',
                'name' => 'Annual Leave',
                'is_paid' => true,
                'status' => 'active',
            ]);

            // Policy effective 2026-01-01 to 2026-12-31, max 3 consecutive days, 5 annual days, probation restricted
            $this->postJson('/api/v1/hr/leave-policies', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'leave_type_id' => $annualType->id,
                'name' => '2026 Annual Policy',
                'annual_allocation_days' => '5.00',
                'max_consecutive_days' => 3,
                'probation_restricted' => true,
                'effective_from' => '2026-01-01',
                'effective_to' => '2026-12-31',
            ])->assertStatus(201);

            // 1. Probation employee rejected
            $this->postJson('/api/v1/hr/leave-requests', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $probationEmp->id,
                'leave_type_id' => $annualType->id,
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-10',
                'reason' => 'Personal',
            ])->assertStatus(422);

            // 2. Out-of-effective-range rejected (2027-02-01)
            $this->postJson('/api/v1/hr/leave-requests', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $permanentEmp->id,
                'leave_type_id' => $annualType->id,
                'start_date' => '2027-02-01',
                'end_date' => '2027-02-01',
                'reason' => 'Next year',
            ])->assertStatus(422);

            // 3. Exceeding max_consecutive_days (4 days > 3) rejected
            $this->postJson('/api/v1/hr/leave-requests', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $permanentEmp->id,
                'leave_type_id' => $annualType->id,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-04',
                'reason' => 'Too long',
            ])->assertStatus(422);

            // 4. Valid 3-day leave auto-seeds balance from policy (5.00) and succeeds
            $this->postJson('/api/v1/hr/leave-requests', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $permanentEmp->id,
                'leave_type_id' => $annualType->id,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-03',
                'reason' => 'Vacation 1',
            ])->assertStatus(201)
                ->assertJsonPath('data.total_days', '3.00');

            // 5. Second 3-day leave exceeds annual_allocation_days (3 + 3 = 6 > 5) -> rejected
            $this->postJson('/api/v1/hr/leave-requests', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $permanentEmp->id,
                'leave_type_id' => $annualType->id,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-03',
                'reason' => 'Vacation 2',
            ])->assertStatus(422);
        });
    }

    public function test_cross_year_leave_balance_split_and_server_side_total_days_with_holidays_and_sandwich_rule(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $this->actingAs(UserModel::query()->findOrFail($userId));
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-XY']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Cross Year Emp', 'EMP-XY-1');

            $leaveType = HrLeaveType::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'ANNUAL-XY',
                'name' => 'Annual Leave',
                'is_paid' => true,
                'sandwich_rule_applied' => false,
                'status' => 'active',
            ]);

            // Holiday on 2027-01-01
            $cal2027 = HrHolidayCalendar::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'CAL-2027',
                'name' => '2027 Calendar',
                'calendar_year' => 2027,
                'is_default' => true,
            ]);
            HrHoliday::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'calendar_id' => $cal2027->id,
                'name' => 'New Year Day',
                'holiday_date' => '2027-01-01',
                'is_mandatory' => true,
                'is_comp_off_eligible' => true,
            ]);

            $bal2026 = HrLeaveBalance::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'calendar_year' => 2026,
                'opening_balance' => '0.00',
                'accrued_days' => '5.00',
                'carried_forward_days' => '0.00',
                'used_days' => '0.00',
                'pending_approval_days' => '0.00',
                'expired_days' => '0.00',
                'current_balance' => '5.00',
            ]);
            $bal2027 = HrLeaveBalance::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'calendar_year' => 2027,
                'opening_balance' => '0.00',
                'accrued_days' => '5.00',
                'carried_forward_days' => '0.00',
                'used_days' => '0.00',
                'pending_approval_days' => '0.00',
                'expired_days' => '0.00',
                'current_balance' => '5.00',
            ]);

            // Apply Dec 30, 2026 to Jan 02, 2027 with bogus client total_days = 1.00.
            // 2027-01-01 is a holiday and sandwich_rule_applied=false -> 2026: 2 days (Dec 30, 31), 2027: 1 day (Jan 02) = 3.00 days!
            $applyRes = $this->postJson('/api/v1/hr/leave-requests', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-12-30',
                'end_date' => '2027-01-02',
                'total_days' => '1.00',
                'reason' => 'Year-end break',
            ])->assertStatus(201);

            $applyRes->assertJsonPath('data.total_days', '3.00')
                ->assertJsonPath('data.row_version', 1);
            $reqId = (int) $applyRes->json('data.id');

            $bal2026->refresh();
            $bal2027->refresh();
            $this->assertSame('2.00', (string) $bal2026->pending_approval_days);
            $this->assertSame('1.00', (string) $bal2027->pending_approval_days);

            // Stale OCC on action -> 409
            $this->patchJson("/api/v1/hr/leave-requests/{$reqId}/action", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 99,
                'approve' => true,
            ])->assertStatus(409);

            // Approve with valid expected_version=1 -> deducts 2.00 from 2026 and 1.00 from 2027
            $this->patchJson("/api/v1/hr/leave-requests/{$reqId}/action", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'approve' => true,
            ])->assertStatus(200)
                ->assertJsonPath('data.status', LeaveRequestStatus::Approved->value);

            $bal2026->refresh();
            $bal2027->refresh();
            $this->assertSame('2.00', (string) $bal2026->used_days);
            $this->assertSame('3.00', (string) $bal2026->current_balance);
            $this->assertSame('1.00', (string) $bal2027->used_days);
            $this->assertSame('4.00', (string) $bal2027->current_balance);

            // Cancel with valid expected_version=2 -> restores 2.00 to 2026 and 1.00 to 2027
            $this->postJson("/api/v1/hr/leave-requests/{$reqId}/cancel", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 2,
                'reason' => 'Plans changed',
            ])->assertStatus(200)
                ->assertJsonPath('data.status', LeaveRequestStatus::Cancelled->value);

            $bal2026->refresh();
            $bal2027->refresh();
            $this->assertSame('0.00', (string) $bal2026->used_days);
            $this->assertSame('5.00', (string) $bal2026->current_balance);
            $this->assertSame('0.00', (string) $bal2027->used_days);
            $this->assertSame('5.00', (string) $bal2027->current_balance);

            // Sandwich rule = true includes the holiday -> 4.00 days
            $sandwichType = HrLeaveType::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'SANDWICH',
                'name' => 'Sandwich Leave',
                'is_paid' => false,
                'sandwich_rule_applied' => true,
                'status' => 'active',
            ]);

            $this->postJson('/api/v1/hr/leave-requests', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'leave_type_id' => $sandwichType->id,
                'start_date' => '2026-12-30',
                'end_date' => '2027-01-02',
                'reason' => 'Sandwich test',
            ])->assertStatus(201)
                ->assertJsonPath('data.total_days', '4.00');
        });
    }

    public function test_comp_off_claim_requires_qualifying_holiday_or_rest_day_attendance_and_prevents_duplicates(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $this->actingAs(UserModel::query()->findOrFail($userId));
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-CO']);
            $employee = $this->createEmployee($tenantId, $orgId, 'CompOff Emp', 'EMP-CO-1');

            $shift = HrShift::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'DAY-CO',
                'name' => 'Day Shift',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'status' => 'active',
            ]);

            // Monday (2026-11-23) is a regular working day
            HrShiftAssignment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'assignment_type' => ShiftAssignmentType::Recurring,
                'day_of_week' => 1,
                'assigned_by' => $userId,
                'assigned_at' => now(),
            ]);

            // 1. No attendance record on 2026-11-22 -> rejected
            $this->postJson('/api/v1/hr/comp-off/claim', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'worked_date' => '2026-11-22',
                'claimed_days' => '1.00',
                'reason' => 'No attendance',
            ])->assertStatus(422);

            // 2. Worked on a regular working day (Monday 2026-11-23) -> rejected
            HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-11-23',
                'shift_id' => $shift->id,
                'status' => AttendanceStatus::Present,
                'total_worked_minutes' => 480,
                'lock_status' => AttendanceLockStatus::Open,
            ]);
            $this->postJson('/api/v1/hr/comp-off/claim', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'worked_date' => '2026-11-23',
                'claimed_days' => '1.00',
                'reason' => 'Regular working day',
            ])->assertStatus(422);

            // 3. Worked on Sunday rest day (2026-11-22) -> accepted & linked to attendance record
            $restRecord = HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-11-22',
                'status' => AttendanceStatus::RestDay,
                'total_worked_minutes' => 480,
                'lock_status' => AttendanceLockStatus::Open,
            ]);

            $claimRes = $this->postJson('/api/v1/hr/comp-off/claim', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'worked_date' => '2026-11-22',
                'claimed_days' => '1.00',
                'reason' => 'Sunday maintenance',
            ])->assertStatus(201);

            $claimId = (int) $claimRes->json('data.id');
            $claimRes->assertJsonPath('data.attendance_record_id', $restRecord->id)
                ->assertJsonPath('data.row_version', 1);

            // Unscheduled employee with Present status on a non-holiday cannot claim comp-off
            $unscheduledEmp = $this->createEmployee($tenantId, $orgId, 'Unscheduled Emp', 'EMP-CO-2');
            HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $unscheduledEmp->id,
                'attendance_date' => '2026-11-24',
                'status' => AttendanceStatus::Present,
                'total_worked_minutes' => 480,
                'lock_status' => AttendanceLockStatus::Open,
            ]);
            $this->postJson('/api/v1/hr/comp-off/claim', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $unscheduledEmp->id,
                'worked_date' => '2026-11-24',
                'claimed_days' => '1.00',
                'reason' => 'Normal day without schedule',
            ])->assertStatus(422);

            // 4. Duplicate claim for 2026-11-22 -> rejected
            $this->postJson('/api/v1/hr/comp-off/claim', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'worked_date' => '2026-11-22',
                'claimed_days' => '1.00',
                'reason' => 'Duplicate claim',
            ])->assertStatus(422);

            // 5. OCC stale check on actionCompOffClaim -> 409
            $this->patchJson("/api/v1/hr/comp-off/claims/{$claimId}/action", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 99,
                'approve' => true,
            ])->assertStatus(409);

            // 6. Approve with valid expected_version=1 -> credits COMP_OFF balance
            $this->patchJson("/api/v1/hr/comp-off/claims/{$claimId}/action", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'approve' => true,
            ])->assertStatus(200)
                ->assertJsonPath('data.status', CompOffStatus::Approved->value);

            $this->assertTrue(
                HrLeaveBalance::query()
                    ->where('tenant_id', $tenantId)
                    ->where('employee_id', $employee->id)
                    ->whereHas('leaveType', fn ($q) => $q->where('code', 'COMP_OFF'))
                    ->where('current_balance', '1.00')
                    ->exists()
            );
        });
    }

    private function tenant(): int
    {
        $now = now();
        $code = Str::upper(Str::random(6));

        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Leave Phase2 Tenant '.$code,
            'slug' => 'leave-phase2-'.strtolower($code),
            'status' => 'active',
            'status_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActiveTenantSubscriptionFixture::create($tenantId);

        return $tenantId;
    }

    private function createUser(int $tenantId): int
    {
        return TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'email' => 'leave-admin-'.Str::lower(Str::random(6)).'@example.test',
        ]);
    }

    private function createEmployee(
        int $tenantId,
        int $orgId,
        string $name,
        string $number,
        ?int $departmentId = null,
        ?int $employmentTypeId = null,
    ): HrEmployee {
        if ($departmentId === null) {
            $dept = HrDepartment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'DEPT-'.Str::upper(Str::random(4)),
                'name' => 'Operations',
            ]);
            $departmentId = (int) $dept->id;
        }

        return HrEmployee::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgId,
            'employee_number' => $number,
            'first_name' => $name,
            'display_name' => $name,
            'department_id' => $departmentId,
            'employment_type_id' => $employmentTypeId,
            'status' => EmployeeStatus::Active,
        ]);
    }
}

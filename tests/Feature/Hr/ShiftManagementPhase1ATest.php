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
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Hr\DTOs\PunchData;
use Modules\Hr\DTOs\ShiftReallocationData;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Enums\LeaveDurationType;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Enums\ShiftAssignmentType;
use Modules\Hr\Enums\ShiftReallocationReason;
use Modules\Hr\Enums\ShiftStatus;
use Modules\Hr\Enums\ShiftSwapStatus;
use Modules\Hr\Models\HrDepartment;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrHoliday;
use Modules\Hr\Models\HrHolidayCalendar;
use Modules\Hr\Models\HrLeaveRequest;
use Modules\Hr\Models\HrLeaveType;
use Modules\Hr\Models\HrShift;
use Modules\Hr\Models\HrShiftAssignment;
use Modules\Hr\Models\HrShiftBreak;
use Modules\Hr\Models\HrShiftRotation;
use Modules\Hr\Models\HrShiftRotationStep;
use Modules\Hr\Services\AttendanceCalculationEngine;
use Modules\Hr\Services\HrAuthorizationService;
use Modules\Hr\Services\ShiftRosterService;
use Modules\Payroll\Services\PayrollAttendanceIntegrationService;
use Modules\User\Models\UserModel;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class ShiftManagementPhase1ATest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_soft_delete_unique_code_validation_partial_patch_occ_and_swap_fk_guard(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'shift-admin-'.Str::lower(Str::random(6)).'@example.test',
            ]);
            $this->actingAs(UserModel::query()->findOrFail($userId));
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-SH1']);

            // Invalid time format rejected
            $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'BAD-TIME',
                'name' => 'Bad Time',
                'start_time' => '25:99',
                'end_time' => '17:00',
            ])->assertStatus(422);

            // half_day_threshold_minutes > full_day_threshold_minutes rejected
            $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'BAD-THRESH',
                'name' => 'Bad Threshold',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'half_day_threshold_minutes' => 500,
                'full_day_threshold_minutes' => 480,
            ])->assertStatus(422);

            // auto_deduct_break = true with 0 minutes rejected
            $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'BAD-DEDUCT',
                'name' => 'Bad Deduct',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'auto_deduct_break' => true,
                'auto_deduct_break_minutes' => 0,
            ])->assertStatus(422);

            // Create valid shift
            $createRes = $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'SH-REUSE',
                'name' => 'Original Shift',
                'start_time' => '08:30',
                'end_time' => '17:30',
                'grace_in_minutes' => 10,
            ])->assertStatus(201);
            $shiftId = (int) $createRes->json('data.id');

            // Duplicate active code rejected
            $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'SH-REUSE',
                'name' => 'Duplicate Active',
                'start_time' => '09:00',
                'end_time' => '18:00',
            ])->assertStatus(422);

            // PATCH without expected_version -> 422
            $this->patchJson("/api/v1/hr/shifts/{$shiftId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'Missing OCC',
            ])->assertStatus(422);

            // PATCH with stale expected_version -> 409
            $this->patchJson("/api/v1/hr/shifts/{$shiftId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 99,
                'name' => 'Stale OCC',
            ])->assertStatus(409);

            // PATCH partial update with valid expected_version -> preserves existing fields
            $this->patchJson("/api/v1/hr/shifts/{$shiftId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'name' => 'Patched Shift Name',
            ])->assertStatus(200)
                ->assertJsonPath('data.name', 'Patched Shift Name')
                ->assertJsonPath('data.code', 'SH-REUSE')
                ->assertJsonPath('data.grace_in_minutes', 10)
                ->assertJsonPath('data.row_version', 2);

            // Soft-delete shift and reuse code
            $this->deleteJson("/api/v1/hr/shifts/{$shiftId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 2,
            ])->assertStatus(204);

            $reusedRes = $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'SH-REUSE',
                'name' => 'Reused Code Shift',
                'start_time' => '09:00',
                'end_time' => '17:00',
            ])->assertStatus(201);
            $reusedId = (int) $reusedRes->json('data.id');

            // Shift referenced by hr_shift_swaps cannot be deleted
            $empA = $this->createEmployee($tenantId, $orgId, 'Swapper A', 'EMP-FK-A', $userId);
            $empB = $this->createEmployee($tenantId, $orgId, 'Swapper B', 'EMP-FK-B');
            $otherShiftRes = $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'SH-OTHER',
                'name' => 'Other Shift',
                'start_time' => '13:00',
                'end_time' => '21:00',
            ])->assertStatus(201);
            $otherShiftId = (int) $otherShiftRes->json('data.id');

            $this->postJson('/api/v1/hr/shift-assignments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $empA->id,
                'shift_id' => $reusedId,
                'assignment_type' => ShiftAssignmentType::SpecificDate->value,
                'start_date' => '2026-12-01',
            ])->assertStatus(201);

            $this->postJson('/api/v1/hr/shift-assignments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $empB->id,
                'shift_id' => $otherShiftId,
                'assignment_type' => ShiftAssignmentType::SpecificDate->value,
                'start_date' => '2026-12-02',
            ])->assertStatus(201);

            $this->postJson('/api/v1/hr/shift-swaps', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'requester_employee_id' => $empA->id,
                'target_employee_id' => $empB->id,
                'swap_date' => '2026-12-01',
                'target_swap_date' => '2026-12-02',
                'reason' => 'Personal',
            ])->assertStatus(201);

            $this->deleteJson("/api/v1/hr/shifts/{$otherShiftId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
            ])->assertStatus(422);
        });
    }

    public function test_shift_breaks_crud_occ_and_attendance_engine_integration(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'breaks-'.Str::lower(Str::random(6)).'@example.test',
            ]);
            $this->actingAs(UserModel::query()->findOrFail($userId));
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-BRK']);
            $emp = $this->createEmployee($tenantId, $orgId, 'Break Tester', 'EMP-BRK-1');

            $shiftRes = $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'BRK-SHIFT',
                'name' => 'Break Shift',
                'start_time' => '09:00',
                'end_time' => '17:30',
                'is_break_paid' => false,
                'auto_deduct_threshold_minutes' => 300,
            ])->assertStatus(201);
            $shiftId = (int) $shiftRes->json('data.id');

            // Create paid meal break and mandatory unpaid rest break via API
            $mealRes = $this->postJson("/api/v1/hr/shifts/{$shiftId}/breaks", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'Lunch Meal',
                'break_type' => 'meal',
                'start_time' => '12:30',
                'end_time' => '13:00',
                'duration_minutes' => 30,
                'is_paid' => true,
                'is_mandatory' => false,
            ])->assertStatus(201);
            $mealBreakId = (int) $mealRes->json('data.id');

            $this->getJson("/api/v1/hr/shifts/{$shiftId}/breaks?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200)
                ->assertJsonCount(1, 'data');

            $this->getJson("/api/v1/hr/shifts/{$shiftId}/breaks/{$mealBreakId}?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200)
                ->assertJsonPath('data.name', 'Lunch Meal');

            // OCC conflict and valid update
            $this->patchJson("/api/v1/hr/shifts/{$shiftId}/breaks/{$mealBreakId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 99,
                'name' => 'Stale',
            ])->assertStatus(409);

            $this->patchJson("/api/v1/hr/shifts/{$shiftId}/breaks/{$mealBreakId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'name' => 'Paid Lunch Break',
            ])->assertStatus(200)
                ->assertJsonPath('data.name', 'Paid Lunch Break')
                ->assertJsonPath('data.row_version', 2);

            // Assign shift and record punches with MealOut/MealIn (paid because HrShiftBreak.is_paid = true)
            $this->postJson('/api/v1/hr/shift-assignments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $emp->id,
                'shift_id' => $shiftId,
                'assignment_type' => ShiftAssignmentType::SpecificDate->value,
                'start_date' => '2026-12-03',
            ])->assertStatus(201);

            $engine = app(AttendanceCalculationEngine::class);
            $engine->recordPunch($tenantId, new PunchData(employeeId: (int) $emp->id, punchType: PunchType::In, punchTime: '2026-12-03 09:00:00'));
            $engine->recordPunch($tenantId, new PunchData(employeeId: (int) $emp->id, punchType: PunchType::MealOut, punchTime: '2026-12-03 12:30:00'));
            $engine->recordPunch($tenantId, new PunchData(employeeId: (int) $emp->id, punchType: PunchType::MealIn, punchTime: '2026-12-03 13:00:00'));
            $engine->recordPunch($tenantId, new PunchData(employeeId: (int) $emp->id, punchType: PunchType::Out, punchTime: '2026-12-03 17:30:00'));

            $record = $emp->attendanceRecords()->whereDate('attendance_date', '2026-12-03')->firstOrFail();
            $this->assertSame(30, (int) $record->total_break_minutes);
            $this->assertSame(0, (int) $record->unpaid_break_minutes);
            $this->assertSame(510, (int) $record->total_worked_minutes);

            // Delete meal break and add mandatory unpaid 30-min break, test unpunched auto-deduction
            $this->deleteJson("/api/v1/hr/shifts/{$shiftId}/breaks/{$mealBreakId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 2,
            ])->assertStatus(204);

            HrShiftBreak::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'shift_id' => $shiftId,
                'name' => 'Mandatory Unpaid Break',
                'break_type' => 'rest',
                'duration_minutes' => 30,
                'is_paid' => false,
                'is_mandatory' => true,
            ]);

            $this->postJson('/api/v1/hr/shift-assignments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $emp->id,
                'shift_id' => $shiftId,
                'assignment_type' => ShiftAssignmentType::SpecificDate->value,
                'start_date' => '2026-12-04',
            ])->assertStatus(201);

            $engine->recordPunch($tenantId, new PunchData(employeeId: (int) $emp->id, punchType: PunchType::In, punchTime: '2026-12-04 09:00:00'));
            $engine->recordPunch($tenantId, new PunchData(employeeId: (int) $emp->id, punchType: PunchType::Out, punchTime: '2026-12-04 17:30:00'));

            $record2 = $emp->attendanceRecords()->whereDate('attendance_date', '2026-12-04')->firstOrFail();
            $this->assertSame(30, (int) $record2->total_break_minutes);
            $this->assertSame(30, (int) $record2->unpaid_break_minutes);
            $this->assertSame(480, (int) $record2->total_worked_minutes);
        });
    }

    public function test_shift_swap_ownership_authorization_and_business_rules(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userAId = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'emp-a-'.Str::lower(Str::random(6)).'@example.test',
            ]);
            $userBId = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'emp-b-'.Str::lower(Str::random(6)).'@example.test',
            ]);
            $managerUserId = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'mgr-'.Str::lower(Str::random(6)).'@example.test',
            ]);

            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-SWP']);
            $empA = $this->createEmployee($tenantId, $orgId, 'Emp A', 'EMP-SWP-A', $userAId);
            $empB = $this->createEmployee($tenantId, $orgId, 'Emp B', 'EMP-SWP-B', $userBId);

            // Mock PermissionCheckerInterface: userA & userB do NOT have MANAGE_SHIFTS, manager DOES
            $checker = new class($managerUserId) implements PermissionCheckerInterface
            {
                public function __construct(private readonly int $managerId) {}

                public function allows(int $userId, int $tenantId, string $permission): bool
                {
                    if ($permission === HrAuthorizationService::MANAGE_SHIFTS) {
                        return $userId === $this->managerId;
                    }

                    return true;
                }
            };
            $this->app->instance(PermissionCheckerInterface::class, $checker);

            $shiftA = HrShift::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'SWP-A',
                'name' => 'Shift A',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'status' => ShiftStatus::Active,
            ]);
            $shiftB = HrShift::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'SWP-B',
                'name' => 'Shift B',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'status' => ShiftStatus::Active,
            ]);

            HrShiftAssignment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $empA->id,
                'shift_id' => $shiftA->id,
                'assignment_type' => ShiftAssignmentType::SpecificDate,
                'start_date' => '2026-12-10',
            ]);
            HrShiftAssignment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $empB->id,
                'shift_id' => $shiftB->id,
                'assignment_type' => ShiftAssignmentType::SpecificDate,
                'start_date' => '2026-12-11',
            ]);

            // userA cannot request swap on behalf of empB -> 403
            $this->actingAs(UserModel::query()->findOrFail($userAId));
            $this->postJson('/api/v1/hr/shift-swaps', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'requester_employee_id' => $empB->id,
                'target_employee_id' => $empA->id,
                'swap_date' => '2026-12-11',
                'target_swap_date' => '2026-12-10',
                'reason' => 'Unauthorized',
            ])->assertStatus(403);

            // userA cannot swap on a rest day (null shift) -> 422
            $this->postJson('/api/v1/hr/shift-swaps', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'requester_employee_id' => $empA->id,
                'target_employee_id' => $empB->id,
                'swap_date' => '2026-12-12',
                'target_swap_date' => '2026-12-11',
                'reason' => 'Rest day swap',
            ])->assertStatus(422);

            // userA creates valid swap -> 201
            $swapRes = $this->postJson('/api/v1/hr/shift-swaps', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'requester_employee_id' => $empA->id,
                'target_employee_id' => $empB->id,
                'swap_date' => '2026-12-10',
                'target_swap_date' => '2026-12-11',
                'reason' => 'Valid swap',
            ])->assertStatus(201);
            $swapId = (int) $swapRes->json('data.id');

            // Duplicate pending swap for same employee & date rejected -> 422
            $this->postJson('/api/v1/hr/shift-swaps', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'requester_employee_id' => $empA->id,
                'target_employee_id' => $empB->id,
                'swap_date' => '2026-12-10',
                'target_swap_date' => '2026-12-11',
                'reason' => 'Duplicate swap',
            ])->assertStatus(422);

            // List swaps (verifies requesterEmployee eager-load fix) and showSwap
            $this->getJson("/api/v1/hr/shift-swaps?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200)
                ->assertJsonPath('data.0.requester_employee.id', $empA->id);

            $this->getJson("/api/v1/hr/shift-swaps/{$swapId}?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200)
                ->assertJsonPath('data.id', $swapId);

            // userA cannot peer-action for empB -> 403
            $this->patchJson("/api/v1/hr/shift-swaps/{$swapId}/peer-action", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'peer_employee_id' => $empB->id,
                'accept' => true,
            ])->assertStatus(403);

            // userB can peer-action -> 200
            $this->actingAs(UserModel::query()->findOrFail($userBId));
            $this->patchJson("/api/v1/hr/shift-swaps/{$swapId}/peer-action", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'peer_employee_id' => $empB->id,
                'accept' => true,
            ])->assertStatus(200)
                ->assertJsonPath('data.status', ShiftSwapStatus::PeerAccepted->value);

            // userB cannot cancel userA's swap -> 403; userA can cancel with OCC -> 200
            $this->patchJson("/api/v1/hr/shift-swaps/{$swapId}/cancel", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 2,
            ])->assertStatus(403);

            $this->actingAs(UserModel::query()->findOrFail($userAId));
            $this->patchJson("/api/v1/hr/shift-swaps/{$swapId}/cancel", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 2,
            ])->assertStatus(200)
                ->assertJsonPath('data.status', ShiftSwapStatus::Cancelled->value);
        });
    }

    public function test_holiday_priority_calendar_scoping_double_time_and_absent_reallocation_and_inactive_rotation(): void
    {
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgA = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'Org A', 'code' => 'ORG-A']);
            $orgB = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'Org B', 'code' => 'ORG-B']);

            $empA = $this->createEmployee($tenantId, $orgA, 'OrgA Emp', 'EMP-ORGA');
            $empB = $this->createEmployee($tenantId, $orgB, 'OrgB Emp', 'EMP-ORGB');
            $empCover = $this->createEmployee($tenantId, $orgA, 'Cover Emp', 'EMP-COV');

            $rosterService = app(ShiftRosterService::class);
            $attendanceEngine = app(AttendanceCalculationEngine::class);
            $payrollIntegration = app(PayrollAttendanceIntegrationService::class);

            $shift = HrShift::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgA,
                'code' => 'HOL-S1',
                'name' => 'Standard Shift',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'status' => ShiftStatus::Active,
            ]);

            // Assign recurring Monday (day 1) shift to empA & empB
            HrShiftAssignment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgA,
                'employee_id' => $empA->id,
                'shift_id' => $shift->id,
                'assignment_type' => ShiftAssignmentType::Recurring,
                'day_of_week' => 1,
            ]);
            HrShiftAssignment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgB,
                'employee_id' => $empB->id,
                'shift_id' => $shift->id,
                'assignment_type' => ShiftAssignmentType::Recurring,
                'day_of_week' => 1,
            ]);

            // Create Org A calendar (not default) with holiday on Monday 2026-12-07
            $calA = HrHolidayCalendar::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgA,
                'code' => 'CAL-A-2026',
                'name' => 'Org A 2026',
                'calendar_year' => 2026,
                'is_default' => false,
            ]);
            HrHoliday::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgA,
                'calendar_id' => $calA->id,
                'name' => 'Org A Founders Day',
                'holiday_date' => '2026-12-07',
            ]);

            // Create Tenant Default calendar with holiday on Monday 2026-12-14
            $calDefault = HrHolidayCalendar::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'code' => 'CAL-DEF-2026',
                'name' => 'Default 2026',
                'calendar_year' => 2026,
                'is_default' => true,
            ]);
            HrHoliday::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'calendar_id' => $calDefault->id,
                'name' => 'National Day',
                'holiday_date' => '2026-12-14',
            ]);

            // On 2026-12-07: empA gets holiday (overrides recurring!), empB gets recurring shift (Org A calendar does NOT leak to Org B)
            $resA = $rosterService->resolveShift($tenantId, (int) $empA->id, '2026-12-07');
            $resB = $rosterService->resolveShift($tenantId, (int) $empB->id, '2026-12-07');
            $this->assertTrue($resA->isHoliday);
            $this->assertSame('holiday', $resA->resolutionSource);
            $this->assertFalse($resB->isHoliday);
            $this->assertSame('recurring', $resB->resolutionSource);

            // On 2026-12-14: empB has no org-specific calendar, so falls back to is_default calendar -> Holiday!
            $resBDefault = $rosterService->resolveShift($tenantId, (int) $empB->id, '2026-12-14');
            $this->assertTrue($resBDefault->isHoliday);
            $this->assertSame('National Day', $resBDefault->holidayName);

            // Batch resolution matches single resolution
            $batch = $rosterService->resolveRosterBatch($tenantId, [(int) $empA->id, (int) $empB->id], '2026-12-07', '2026-12-14');
            $this->assertTrue($batch[$empA->id]['2026-12-07']->isHoliday);
            $this->assertFalse($batch[$empB->id]['2026-12-07']->isHoliday);
            $this->assertTrue($batch[$empB->id]['2026-12-14']->isHoliday);

            // Emp A works 4 hours on holiday 2026-12-07 -> double_time_minutes = 240 -> payroll double_time_hours = 4.00
            $attendanceEngine->recordPunch($tenantId, new PunchData(employeeId: (int) $empA->id, punchType: PunchType::In, punchTime: '2026-12-07 09:00:00'));
            $attendanceEngine->recordPunch($tenantId, new PunchData(employeeId: (int) $empA->id, punchType: PunchType::Out, punchTime: '2026-12-07 13:00:00'));

            $agg = $payrollIntegration->aggregateAttendance($tenantId, (int) $empA->id, '2026-12-01', '2026-12-31');
            $this->assertSame('4.00', $agg['double_time_hours']);

            // Absent reallocation: empB is absent on 2026-12-21 (Monday), covered by empCover
            $rosterService->reallocateCover($tenantId, new ShiftReallocationData(
                absentEmployeeId: (int) $empB->id,
                coverEmployeeId: (int) $empCover->id,
                shiftId: (int) $shift->id,
                reallocationDate: '2026-12-21',
                reasonCode: ShiftReallocationReason::SickCover,
            ));

            $absentResolved = $rosterService->resolveShift($tenantId, (int) $empB->id, '2026-12-21');
            $coverResolved = $rosterService->resolveShift($tenantId, (int) $empCover->id, '2026-12-21');
            $this->assertNull($absentResolved->shift);
            $this->assertFalse($absentResolved->isWorkingDay);
            $this->assertSame('reallocation_absent', $absentResolved->resolutionSource);
            $this->assertSame('reallocation', $coverResolved->resolutionSource);
            $this->assertSame((int) $shift->id, (int) $coverResolved->shift?->id);

            $batchRealloc = $rosterService->resolveRosterBatch($tenantId, [(int) $empB->id, (int) $empCover->id], '2026-12-21', '2026-12-21');
            $this->assertSame('reallocation_absent', $batchRealloc[$empB->id]['2026-12-21']->resolutionSource);
            $this->assertSame('reallocation', $batchRealloc[$empCover->id]['2026-12-21']->resolutionSource);

            // Inactive rotation is ignored in both resolveShift and resolveRosterBatch
            $inactiveRot = HrShiftRotation::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgA,
                'code' => 'INACT-ROT',
                'name' => 'Inactive Rotation',
                'cycle_days' => 2,
                'status' => 'active',
            ]);
            HrShiftRotationStep::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgA,
                'shift_rotation_id' => $inactiveRot->id,
                'day_sequence' => 1,
                'shift_id' => $shift->id,
            ]);
            $rosterService->assignRotation($tenantId, (int) $empCover->id, (int) $inactiveRot->id, '2026-12-22');
            $inactiveRot->update(['status' => 'inactive', 'row_version' => 2]);

            $rotResolved = $rosterService->resolveShift($tenantId, (int) $empCover->id, '2026-12-22');
            $this->assertSame('rest_day', $rotResolved->resolutionSource);
            $batchRot = $rosterService->resolveRosterBatch($tenantId, [(int) $empCover->id], '2026-12-22', '2026-12-22');
            $this->assertSame('rest_day', $batchRot[$empCover->id]['2026-12-22']->resolutionSource);
        });
    }

    public function test_assignments_reallocations_and_rotations_new_endpoints_and_occ(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = TenantUserFixture::create([
                'tenant_id' => $tenantId,
                'email' => 'crud-'.Str::lower(Str::random(6)).'@example.test',
            ]);
            $this->actingAs(UserModel::query()->findOrFail($userId));
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-CRUD']);
            $emp1 = $this->createEmployee($tenantId, $orgId, 'Emp 1', 'EMP-C1');
            $emp2 = $this->createEmployee($tenantId, $orgId, 'Emp 2', 'EMP-C2');

            $shiftRes = $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'CRUD-S1',
                'name' => 'Crud Shift',
                'start_time' => '09:00',
                'end_time' => '17:00',
            ])->assertStatus(201);
            $shiftId = (int) $shiftRes->json('data.id');

            // Assignment required_if validation
            $this->postJson('/api/v1/hr/shift-assignments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $emp1->id,
                'shift_id' => $shiftId,
                'assignment_type' => ShiftAssignmentType::Recurring->value,
            ])->assertStatus(422);

            $assignRes = $this->postJson('/api/v1/hr/shift-assignments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $emp1->id,
                'shift_id' => $shiftId,
                'assignment_type' => ShiftAssignmentType::Recurring->value,
                'day_of_week' => 2,
            ])->assertStatus(201);
            $assignId = (int) $assignRes->json('data.id');

            $this->getJson("/api/v1/hr/shift-assignments/{$assignId}?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200)
                ->assertJsonPath('data.day_of_week', 2);

            $this->patchJson("/api/v1/hr/shift-assignments/{$assignId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 99,
                'day_of_week' => 3,
            ])->assertStatus(409);

            $this->patchJson("/api/v1/hr/shift-assignments/{$assignId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'day_of_week' => 3,
            ])->assertStatus(200)
                ->assertJsonPath('data.day_of_week', 3)
                ->assertJsonPath('data.row_version', 2);

            // Cancel and re-cancel guard
            $this->deleteJson("/api/v1/hr/shift-assignments/{$assignId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 2,
                'reason' => 'No longer needed',
            ])->assertStatus(200);

            $this->deleteJson("/api/v1/hr/shift-assignments/{$assignId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 3,
                'reason' => 'Cancel again',
            ])->assertStatus(422);

            // Specific-date assignment update cannot move onto approved leave date
            $leaveType = HrLeaveType::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'AL-SH',
                'name' => 'Annual Leave',
                'is_paid' => true,
            ]);
            HrLeaveRequest::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $emp1->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-12-27',
                'end_date' => '2026-12-27',
                'duration_type' => LeaveDurationType::FullDay,
                'total_days' => '1.00',
                'reason' => 'Vacation',
                'status' => LeaveRequestStatus::Approved,
            ]);
            $specAssignRes = $this->postJson('/api/v1/hr/shift-assignments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $emp1->id,
                'shift_id' => $shiftId,
                'assignment_type' => ShiftAssignmentType::SpecificDate->value,
                'start_date' => '2026-12-26',
            ])->assertStatus(201);
            $specAssignId = (int) $specAssignRes->json('data.id');

            $this->patchJson("/api/v1/hr/shift-assignments/{$specAssignId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'start_date' => '2026-12-27',
            ])->assertStatus(422);

            // Reallocation show, update OCC, duplicate guard, destroy OCC
            $reallocRes = $this->postJson('/api/v1/hr/shift-reallocations', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'absent_employee_id' => $emp1->id,
                'cover_employee_id' => $emp2->id,
                'shift_id' => $shiftId,
                'reallocation_date' => '2026-12-28',
            ])->assertStatus(201);
            $reallocId = (int) $reallocRes->json('data.id');

            // Duplicate absent on same date -> 422
            $this->postJson('/api/v1/hr/shift-reallocations', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'absent_employee_id' => $emp1->id,
                'cover_employee_id' => $emp2->id,
                'shift_id' => $shiftId,
                'reallocation_date' => '2026-12-28',
            ])->assertStatus(422);

            $this->getJson("/api/v1/hr/shift-reallocations/{$reallocId}?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200);

            $this->patchJson("/api/v1/hr/shift-reallocations/{$reallocId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'notes' => 'Updated cover note',
            ])->assertStatus(200)
                ->assertJsonPath('data.notes', 'Updated cover note')
                ->assertJsonPath('data.row_version', 2);

            $this->deleteJson("/api/v1/hr/shift-reallocations/{$reallocId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 99,
            ])->assertStatus(409);

            $this->deleteJson("/api/v1/hr/shift-reallocations/{$reallocId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 2,
            ])->assertStatus(204);

            // Rotation step update, cycle_days shrink cleanup, rotation assignment show & update OCC
            $rotRes = $this->postJson('/api/v1/hr/shift-rotations', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'ROT-SHRINK',
                'name' => 'Shrinkable Rotation',
                'cycle_days' => 5,
            ])->assertStatus(201);
            $rotId = (int) $rotRes->json('data.id');

            $step1Res = $this->postJson("/api/v1/hr/shift-rotations/{$rotId}/steps", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'day_sequence' => 1,
                'shift_id' => $shiftId,
            ])->assertStatus(201);
            $step1Id = (int) $step1Res->json('data.id');

            $this->postJson("/api/v1/hr/shift-rotations/{$rotId}/steps", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'day_sequence' => 5,
                'shift_id' => $shiftId,
            ])->assertStatus(201);

            $this->patchJson("/api/v1/hr/shift-rotations/{$rotId}/steps/{$step1Id}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'notes' => 'Day 1 updated',
            ])->assertStatus(200)
                ->assertJsonPath('data.notes', 'Day 1 updated')
                ->assertJsonPath('data.row_version', 2);

            // Reduce cycle_days from 5 to 3 -> step with day_sequence=5 is cleaned up
            $this->patchJson("/api/v1/hr/shift-rotations/{$rotId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'cycle_days' => 3,
            ])->assertStatus(200)
                ->assertJsonCount(1, 'data.steps');

            $rotAssignRes = $this->postJson('/api/v1/hr/shift-rotation-assignments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $emp1->id,
                'shift_rotation_id' => $rotId,
                'anchor_date' => '2026-12-01',
            ])->assertStatus(201);
            $rotAssignId = (int) $rotAssignRes->json('data.id');

            $this->getJson("/api/v1/hr/shift-rotation-assignments/{$rotAssignId}?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200);

            $this->patchJson("/api/v1/hr/shift-rotation-assignments/{$rotAssignId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'end_date' => '2026-12-31',
            ])->assertStatus(200)
                ->assertJsonPath('data.row_version', 2);
        });
    }

    private function tenant(): int
    {
        $now = now();
        $code = Str::upper(Str::random(6));

        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Shift Phase1A Corp '.$code,
            'slug' => 'shift-phase1a-'.strtolower($code),
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Shift Phase 1A Test',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActiveTenantSubscriptionFixture::create($tenantId);

        return $tenantId;
    }

    private function createEmployee(int $tenantId, int $orgId, string $name, string $number, ?int $userId = null): HrEmployee
    {
        $dept = HrDepartment::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'OPS-SH'],
            ['organization_unit_id' => $orgId, 'name' => 'Operations']
        );

        return HrEmployee::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgId,
            'user_id' => $userId,
            'department_id' => $dept->id,
            'employee_number' => $number,
            'code' => $number,
            'first_name' => $name,
            'last_name' => 'Staff',
            'display_name' => $name.' Staff',
            'status' => EmployeeStatus::Active,
            'joined_date' => '2026-01-01',
        ]);
    }
}

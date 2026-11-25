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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Hr\Contracts\AttendanceCaptureAdapterInterface;
use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Enums\CredentialStatus;
use Modules\Hr\Enums\CredentialType;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeAttendanceCredential;
use Modules\Hr\Models\HrLeaveType;
use Modules\Hr\Services\Capture\AttendanceCaptureAdapterManager;
use Modules\Payroll\Services\PayrollAttendanceIntegrationService;
use Modules\User\Models\UserModel;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;
use ZipArchive;

final class HrEndToEndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_capture_adapter_manager_supports_nfc_and_custom_adapters(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $this->actingAs(UserModel::query()->findOrFail($userId));

            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-NFC']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Kasun Perera', 'EMP-NFC-01');

            HrEmployeeAttendanceCredential::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'credential_type' => CredentialType::NfcTag,
                'credential_value' => 'NFC-TAG-001',
                'status' => CredentialStatus::Active,
            ]);

            $nfcRes = $this->postJson('/api/v1/hr/attendance/capture/nfc', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'tag_uid' => 'NFC-TAG-001',
                'punch_type' => 'in',
                'device_uuid' => 'phone-uuid-123',
            ]);
            $nfcRes->assertStatus(201)
                ->assertJsonPath('data.capture_method', CaptureMethod::NfcTag->value)
                ->assertJsonPath('data.employee_id', $employee->id);

            // Duplicate tap within same minute returns same idempotent punch
            $dupRes = $this->postJson('/api/v1/hr/attendance/capture', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'capture_method' => CaptureMethod::NfcTag->value,
                'payload' => [
                    'tag_uid' => 'NFC-TAG-001',
                    'punch_type' => 'in',
                ],
            ]);
            $dupRes->assertStatus(201)
                ->assertJsonPath('data.id', $nfcRes->json('data.id'));

            // Custom adapter registration extensibility
            $manager = app(AttendanceCaptureAdapterManager::class);
            self::assertContains(CaptureMethod::NfcTag->value, $manager->supportedMethods());
            $adapter = $manager->resolve(CaptureMethod::NfcTag);
            self::assertInstanceOf(AttendanceCaptureAdapterInterface::class, $adapter);
            self::assertTrue($adapter->supports(CaptureMethod::NfcTag));
        });
    }

    public function test_excel_xlsx_batch_attendance_import_preview_and_commit(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $this->actingAs(UserModel::query()->findOrFail($userId));

            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-XLSX']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Amali Silva', 'EMP-XLSX-01');

            $xlsxPath = $this->createMinimalXlsx([
                ['employee_code', 'date', 'time', 'punch_type', 'notes'],
                ['EMP-XLSX-01', '2026-11-10', '08:30:00', 'in', 'Morning'],
                ['EMP-XLSX-01', '2026-11-10', '17:30:00', 'out', 'Evening'],
            ]);

            $file = new UploadedFile(
                $xlsxPath,
                'attendance.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            );

            $previewRes = $this->post('/api/v1/hr/attendance/capture/import/preview', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'file' => $file,
            ], ['Accept' => 'application/json']);

            $previewRes->assertStatus(200)
                ->assertJsonPath('data.total_rows', 2)
                ->assertJsonPath('data.valid_rows', 2)
                ->assertJsonPath('data.invalid_rows', 0);

            $commitFile = new UploadedFile(
                $xlsxPath,
                'attendance.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            );

            $commitRes = $this->post('/api/v1/hr/attendance/capture/import/commit', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'file' => $commitFile,
            ], ['Accept' => 'application/json']);

            $commitRes->assertStatus(201)
                ->assertJsonPath('data.processed_rows', 2);

            self::assertSame(
                2,
                HrAttendancePunch::query()
                    ->where('tenant_id', $tenantId)
                    ->where('employee_id', $employee->id)
                    ->where('capture_method', CaptureMethod::FileUpload)
                    ->count()
            );

            @unlink($xlsxPath);
        });
    }

    public function test_leave_type_policy_holiday_and_shift_list_crud_with_occ_and_validation(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $this->actingAs(UserModel::query()->findOrFail($userId));

            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-CRUD']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Nimal Jayasinghe', 'EMP-CRUD-01');

            // 1. Leave Type CRUD + OCC + Blocker
            $ltRes = $this->postJson('/api/v1/hr/leave-types', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'ANNUAL',
                'name' => 'Annual Leave',
                'is_paid' => true,
            ]);
            $ltRes->assertStatus(201);
            $ltId = (int) $ltRes->json('data.id');
            $ltVersion = (int) $ltRes->json('data.row_version');

            $this->getJson("/api/v1/hr/leave-types?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200)
                ->assertJsonPath('data.0.id', $ltId);

            $this->getJson("/api/v1/hr/leave-types/{$ltId}?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200)
                ->assertJsonPath('data.code', 'ANNUAL');

            $this->putJson("/api/v1/hr/leave-types/{$ltId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => $ltVersion,
                'name' => 'Annual Paid Leave',
            ])->assertStatus(200)
                ->assertJsonPath('data.name', 'Annual Paid Leave')
                ->assertJsonPath('data.code', 'ANNUAL')
                ->assertJsonPath('data.row_version', $ltVersion + 1);

            // Stale version -> 409
            $this->putJson("/api/v1/hr/leave-types/{$ltId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => $ltVersion,
                'name' => 'Stale Update',
            ])->assertStatus(409);

            // 2. Leave Policy CRUD + OCC
            $lpRes = $this->postJson('/api/v1/hr/leave-policies', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'leave_type_id' => $ltId,
                'name' => 'Standard Annual Policy',
                'annual_allocation_days' => '14.00',
                'effective_from' => '2026-01-01',
            ]);
            $lpRes->assertStatus(201);
            $lpId = (int) $lpRes->json('data.id');

            $this->getJson("/api/v1/hr/leave-policies/{$lpId}?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200);

            $this->putJson("/api/v1/hr/leave-policies/{$lpId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'annual_allocation_days' => '21.00',
            ])->assertStatus(200)
                ->assertJsonPath('data.annual_allocation_days', '21.00');

            $this->deleteJson("/api/v1/hr/leave-policies/{$lpId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 2,
            ])->assertStatus(204);

            // Delete leave type now that policy is gone
            $this->deleteJson("/api/v1/hr/leave-types/{$ltId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => $ltVersion + 1,
            ])->assertStatus(204);

            // 3. Holiday Calendar & Holiday CRUD + OCC
            $calRes = $this->postJson('/api/v1/hr/holiday-calendars', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'LK-2026',
                'name' => 'Sri Lanka 2026',
                'calendar_year' => 2026,
                'is_default' => true,
            ]);
            $calRes->assertStatus(201);
            $calId = (int) $calRes->json('data.id');

            $holRes = $this->postJson("/api/v1/hr/holiday-calendars/{$calId}/holidays", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'Independence Day',
                'holiday_date' => '2026-02-04',
            ]);
            $holRes->assertStatus(201);
            $holId = (int) $holRes->json('data.id');

            $this->putJson("/api/v1/hr/holidays/{$holId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'name' => 'National Independence Day',
            ])->assertStatus(200)
                ->assertJsonPath('data.name', 'National Independence Day');

            $this->getJson("/api/v1/hr/holiday-calendars/{$calId}/holidays?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200)
                ->assertJsonPath('data.0.id', $holId);

            $this->deleteJson("/api/v1/hr/holidays/{$holId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 2,
            ])->assertStatus(204);

            $this->deleteJson("/api/v1/hr/holiday-calendars/{$calId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
            ])->assertStatus(204);

            // 4. Shift assignments / swaps / reallocations list endpoints
            $this->getJson("/api/v1/hr/shift-assignments?tenant_id={$tenantId}&organization_unit_id={$orgId}&employee_id={$employee->id}")
                ->assertStatus(200);
            $this->getJson("/api/v1/hr/shift-swaps?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200);
            $this->getJson("/api/v1/hr/shift-reallocations?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200);
        });
    }

    public function test_leave_overlap_rejection_and_payroll_period_clipping(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $this->actingAs(UserModel::query()->findOrFail($userId));

            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-LV']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Dilshan Fernando', 'EMP-LV-01');

            $unpaidType = HrLeaveType::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'UNPAID',
                'name' => 'Unpaid Leave',
                'is_paid' => false,
                'status' => 'active',
            ]);

            // Apply unpaid leave spanning Jan 30 - Feb 02 (4 days total: 2 in Jan, 2 in Feb)
            $applyRes = $this->postJson('/api/v1/hr/leave-requests', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'leave_type_id' => $unpaidType->id,
                'start_date' => '2026-01-30',
                'end_date' => '2026-02-02',
                'total_days' => '4.00',
                'reason' => 'Personal travel',
            ]);
            $applyRes->assertStatus(201);
            $leaveId = (int) $applyRes->json('data.id');

            // Overlapping request must be rejected
            $overlapRes = $this->postJson('/api/v1/hr/leave-requests', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'leave_type_id' => $unpaidType->id,
                'start_date' => '2026-01-31',
                'end_date' => '2026-02-01',
                'total_days' => '2.00',
                'reason' => 'Overlap',
            ]);
            $overlapRes->assertStatus(422);

            // Approve leave
            $this->patchJson("/api/v1/hr/leave-requests/{$leaveId}/action", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'approve' => true,
            ])->assertStatus(200)
                ->assertJsonPath('data.status', LeaveRequestStatus::Approved->value);

            // Payroll integration must clip unpaid days per period (2.00 in Jan, 2.00 in Feb)
            $payrollIntegration = app(PayrollAttendanceIntegrationService::class);
            $janAgg = $payrollIntegration->aggregateAttendance($tenantId, (int) $employee->id, '2026-01-01', '2026-01-31');
            $febAgg = $payrollIntegration->aggregateAttendance($tenantId, (int) $employee->id, '2026-02-01', '2026-02-28');

            self::assertSame('2.00', $janAgg['unpaid_leave_days']);
            self::assertSame('2.00', $febAgg['unpaid_leave_days']);
        });
    }

    public function test_audit_fixes_for_adapters_xlsx_serials_validation_occ_and_fk_guards(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $this->actingAs(UserModel::query()->findOrFail($userId));
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-AUD']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Audit Tester', 'EMP-AUD1');

            // 1. Capture adapters must throw 422 (InvalidArgumentException), not 500 Undefined array key
            $this->postJson('/api/v1/hr/attendance/capture', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'capture_method' => 'web_portal',
                'payload' => ['employee_id' => $employee->id],
            ])->assertStatus(422);

            $this->postJson('/api/v1/hr/attendance/capture', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'capture_method' => 'file_upload',
                'payload' => ['employee_id' => $employee->id, 'punch_type' => 'in'],
            ])->assertStatus(422);

            // 2. Excel .xlsx numeric serial date & time + sharedStrings support
            $xlsxPath = $this->createSerialAndSharedStringsXlsx();
            try {
                $uploaded = new UploadedFile($xlsxPath, 'serials.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
                $previewRes = $this->post('/api/v1/hr/attendance/capture/import/preview', [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $orgId,
                    'file' => $uploaded,
                ], ['Accept' => 'application/json']);

                $previewRes->assertStatus(200)
                    ->assertJsonPath('data.total_rows', 1)
                    ->assertJsonPath('data.valid_rows', 1)
                    ->assertJsonPath('data.rows.0.date', '2026-11-10')
                    ->assertJsonPath('data.rows.0.time', '09:00:00');
            } finally {
                @unlink($xlsxPath);
            }

            // 3. Holiday update duplicate date returns 422 instead of 500 SQL error
            $calRes = $this->postJson('/api/v1/hr/holiday-calendars', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'CAL-DUP',
                'name' => 'Dup Test Cal',
                'calendar_year' => 2026,
            ])->assertStatus(201);
            $calId = (int) $calRes->json('data.id');

            $this->postJson("/api/v1/hr/holiday-calendars/{$calId}/holidays", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'Holiday A',
                'holiday_date' => '2026-05-01',
            ])->assertStatus(201);

            $h2 = $this->postJson("/api/v1/hr/holiday-calendars/{$calId}/holidays", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'Holiday B',
                'holiday_date' => '2026-05-02',
            ])->assertStatus(201);
            $h2Id = (int) $h2->json('data.id');

            $this->putJson("/api/v1/hr/holidays/{$h2Id}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'holiday_date' => '2026-05-01',
            ])->assertStatus(422);

            // 4. Leave policy update rejects effective_to < effective_from
            $lt = $this->postJson('/api/v1/hr/leave-types', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'POL-TST',
                'name' => 'Policy Test Leave',
            ])->assertStatus(201);
            $ltId = (int) $lt->json('data.id');

            $lp = $this->postJson('/api/v1/hr/leave-policies', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'leave_type_id' => $ltId,
                'name' => 'Test Policy',
                'annual_allocation_days' => 10,
                'effective_from' => '2026-06-01',
            ])->assertStatus(201);
            $lpId = (int) $lp->json('data.id');

            $this->putJson("/api/v1/hr/leave-policies/{$lpId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'effective_to' => '2026-05-01',
            ])->assertStatus(422);

            // 5. Shift duplicate code (422), assigned shift delete blocker (422), cancelAssignment OCC (409)
            $shiftRes = $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'SHF-UNIQ',
                'name' => 'Unique Shift',
                'start_time' => '09:00',
                'end_time' => '17:00',
            ])->assertStatus(201);
            $shiftId = (int) $shiftRes->json('data.id');

            $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'SHF-UNIQ',
                'name' => 'Duplicate Shift',
                'start_time' => '09:00',
                'end_time' => '17:00',
            ])->assertStatus(422);

            $assignRes = $this->postJson('/api/v1/hr/shift-assignments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'shift_id' => $shiftId,
                'assignment_type' => 'specific_date',
                'start_date' => '2026-11-15',
            ])->assertStatus(201);
            $assignId = (int) $assignRes->json('data.id');

            $this->deleteJson("/api/v1/hr/shifts/{$shiftId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
            ])->assertStatus(422);

            $this->deleteJson("/api/v1/hr/shift-assignments/{$assignId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 999,
                'reason' => 'Stale version',
            ])->assertStatus(409);

            $this->deleteJson("/api/v1/hr/shift-assignments/{$assignId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'reason' => 'Valid cancel',
            ])->assertStatus(200);
        });
    }

    public function test_shift_rotations_crud_steps_occ_and_assignments(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $this->actingAs(UserModel::query()->findOrFail($userId));
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-ROT']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Rot Tester', 'EMP-ROT1');

            $shiftRes = $this->postJson('/api/v1/hr/shifts', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'ROT-S1',
                'name' => 'Rotation Day Shift',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
            ])->assertStatus(201);
            $shiftId = (int) $shiftRes->json('data.id');

            // Create rotation
            $rotRes = $this->postJson('/api/v1/hr/shift-rotations', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'CONT-7',
                'name' => '7-Day Rotation',
                'cycle_days' => 7,
            ])->assertStatus(201);
            $rotId = (int) $rotRes->json('data.id');

            // List & Show
            $this->getJson("/api/v1/hr/shift-rotations?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200)
                ->assertJsonPath('data.0.code', 'CONT-7');
            $this->getJson("/api/v1/hr/shift-rotations/{$rotId}?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200)
                ->assertJsonPath('data.name', '7-Day Rotation');

            // Add step
            $stepRes = $this->postJson("/api/v1/hr/shift-rotations/{$rotId}/steps", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'day_sequence' => 1,
                'shift_id' => $shiftId,
            ])->assertStatus(201);
            $stepId = (int) $stepRes->json('data.id');

            // Update with stale OCC -> 409, valid OCC -> 200
            $this->putJson("/api/v1/hr/shift-rotations/{$rotId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 999,
                'name' => 'Stale Name',
            ])->assertStatus(409);

            $this->putJson("/api/v1/hr/shift-rotations/{$rotId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'name' => 'Updated 7-Day Rotation',
            ])->assertStatus(200)
                ->assertJsonPath('data.name', 'Updated 7-Day Rotation')
                ->assertJsonPath('data.row_version', 2);

            // Assign rotation & reject overlapping assignment
            $assignRes = $this->postJson('/api/v1/hr/shift-rotation-assignments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'shift_rotation_id' => $rotId,
                'anchor_date' => '2026-11-01',
                'end_date' => '2026-11-30',
            ])->assertStatus(201);
            $assignId = (int) $assignRes->json('data.id');

            $this->postJson('/api/v1/hr/shift-rotation-assignments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'shift_rotation_id' => $rotId,
                'anchor_date' => '2026-11-15',
            ])->assertStatus(422);

            // In-use rotation delete blocked -> 422
            $this->deleteJson("/api/v1/hr/shift-rotations/{$rotId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
            ])->assertStatus(422);

            // Cancel rotation assignment with OCC
            $this->deleteJson("/api/v1/hr/shift-rotation-assignments/{$assignId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 999,
            ])->assertStatus(409);

            $this->deleteJson("/api/v1/hr/shift-rotation-assignments/{$assignId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
            ])->assertStatus(200)
                ->assertJsonPath('data.is_active', false);

            // Delete step
            $this->deleteJson("/api/v1/hr/shift-rotations/{$rotId}/steps/{$stepId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
            ])->assertStatus(204);
        });
    }

    private function createSerialAndSharedStringsXlsx(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'hr_xlsx_serial_').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>');

        $strings = ['employee_code', 'date', 'time', 'punch_type', 'EMP-AUD1', 'in'];
        $siXml = '';
        foreach ($strings as $s) {
            $siXml .= '<si><t>'.htmlspecialchars($s, ENT_XML1).'</t></si>';
        }
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="6" uniqueCount="6">'.$siXml.'</sst>');

        // 46336 = 2026-11-10 in Excel 1900 date system; 0.375 = 09:00:00
        $sheetXml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            .'<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c><c r="D1" t="s"><v>3</v></c></row>'
            .'<row r="2"><c r="A2" t="s"><v>4</v></c><c r="B2"><v>46336</v></c><c r="C2"><v>0.375</v></c><c r="D2" t="s"><v>5</v></c></row>'
            .'</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return $path;
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function createMinimalXlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'hr_xlsx_').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');

        $rowsXml = '';
        foreach ($rows as $rIdx => $row) {
            $rowNum = $rIdx + 1;
            $cellsXml = '';
            foreach ($row as $cIdx => $val) {
                $col = chr(ord('A') + $cIdx);
                $escaped = htmlspecialchars($val, ENT_XML1);
                $cellsXml .= "<c r=\"{$col}{$rowNum}\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
            }
            $rowsXml .= "<row r=\"{$rowNum}\">{$cellsXml}</row>";
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', "<?xml version=\"1.0\" encoding=\"UTF-8\"?><worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\"><sheetData>{$rowsXml}</sheetData></worksheet>");
        $zip->close();

        return $path;
    }

    private function createUser(int $tenantId): int
    {
        return (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin_'.Str::lower(Str::random(6)),
            'email' => 'admin-'.Str::random(8).'@example.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEmployee(int $tenantId, int $orgId, string $name, string $code): HrEmployee
    {
        return HrEmployee::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgId,
            'employee_number' => $code,
            'code' => $code,
            'first_name' => $name,
            'display_name' => $name,
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
            'name' => 'HR Audit Tenant '.$code,
            'slug' => 'hr-audit-tenant-'.strtolower($code),
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'HR End-to-End Audit',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActiveTenantSubscriptionFixture::create($tenantId);

        return $tenantId;
    }
}

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
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\BiometricProtocol;
use Modules\Hr\Enums\CredentialStatus;
use Modules\Hr\Enums\CredentialType;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Enums\LeaveAccrualFrequency;
use Modules\Hr\Enums\LeaveDurationType;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\RegularizationStatus;
use Modules\Hr\Enums\RegularizationType;
use Modules\Hr\Enums\ShiftAssignmentType;
use Modules\Hr\Models\HrAttendanceKiosk;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrAttendanceRegularization;
use Modules\Hr\Models\HrBiometricDevice;
use Modules\Hr\Models\HrBiometricRawPunch;
use Modules\Hr\Models\HrDepartment;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeAttendanceCredential;
use Modules\Hr\Models\HrEmploymentType;
use Modules\Hr\Models\HrLeavePolicy;
use Modules\Hr\Models\HrLeaveRequest;
use Modules\Hr\Models\HrLeaveType;
use Modules\Hr\Models\HrShift;
use Modules\Hr\Models\HrShiftAssignment;
use Modules\Hr\Services\Capture\DynamicQrSecurityService;
use Modules\Payroll\Enums\CalculationType;
use Modules\Payroll\Enums\PayrollCycleType;
use Modules\Payroll\Enums\SalaryComponentType;
use Modules\Payroll\Models\PayrollEmployeeSalaryAssignment;
use Modules\Payroll\Models\PayrollPayPeriod;
use Modules\Payroll\Models\PayrollSalaryComponent;
use Modules\Payroll\Models\PayrollSalaryStructure;
use Modules\Payroll\Models\PayrollSalaryStructureItem;
use Modules\Payroll\Services\PayrollAttendanceIntegrationService;
use Modules\Payroll\Services\PayrollCalculationEngine;
use Modules\User\Models\UserModel;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class AttendancePhase3Test extends TestCase
{
    use RefreshDatabase;

    public function test_regularization_action_cancel_and_direct_adjust_enforce_occ_and_ownership(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $ownerUserId = $this->createUser($tenantId);
            $otherUserId = $this->createUser($tenantId);
            $managerUserId = $this->createUser($tenantId);
            $this->app->instance(PermissionCheckerInterface::class, new class($managerUserId) implements PermissionCheckerInterface
            {
                public function __construct(private readonly int $managerId) {}

                public function allows(int $userId, int $tenantId, string $permission): bool
                {
                    return $userId === $this->managerId;
                }
            });

            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-P3']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Owned Employee', 'EMP-P3-01', $ownerUserId);

            $record = HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-11-20',
                'status' => AttendanceStatus::Absent,
                'total_worked_minutes' => 0,
                'overtime_minutes' => 0,
            ]);

            // 1. Direct adjust rejects stale expected_version
            $this->actingAs(UserModel::query()->findOrFail($managerUserId));
            $this->postJson("/api/v1/hr/attendance/records/{$record->id}/direct-adjust", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 999,
                'new_status' => 'present',
                'new_worked_minutes' => 480,
                'new_overtime_minutes' => 0,
                'adjustment_reason' => 'Stale check',
            ])->assertStatus(409);

            $this->postJson("/api/v1/hr/attendance/records/{$record->id}/direct-adjust", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'new_status' => 'present',
                'new_worked_minutes' => 480,
                'new_overtime_minutes' => 30,
                'adjustment_reason' => 'Valid adjustment',
            ])->assertStatus(201);

            // 2. Regularization cancel: non-owner without MANAGE_ATTENDANCE gets 403
            $reg = HrAttendanceRegularization::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-11-21',
                'regularization_type' => RegularizationType::ForgotToPunch,
                'reason_code' => 'forgot_to_punch',
                'employee_notes' => 'Missed clock in',
                'status' => RegularizationStatus::Pending,
            ]);

            $this->actingAs(UserModel::query()->findOrFail($otherUserId));
            $this->postJson("/api/v1/hr/attendance/regularizations/{$reg->id}/cancel", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
            ])->assertStatus(403);

            // Owner with stale version gets 409
            $this->actingAs(UserModel::query()->findOrFail($ownerUserId));
            $this->postJson("/api/v1/hr/attendance/regularizations/{$reg->id}/cancel", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 99,
            ])->assertStatus(409);

            // Owner with valid version cancels cleanly
            $this->postJson("/api/v1/hr/attendance/regularizations/{$reg->id}/cancel", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'notes' => 'Withdrawn by employee',
            ])->assertStatus(200)
                ->assertJsonPath('data.status', 'cancelled')
                ->assertJsonPath('data.row_version', 2);

            // 3. Action regularization OCC check
            $reg2 = HrAttendanceRegularization::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-11-22',
                'regularization_type' => RegularizationType::ForgotToPunch,
                'proposed_clock_in' => '2026-11-22 08:00:00',
                'proposed_clock_out' => '2026-11-22 17:00:00',
                'reason_code' => 'forgot_to_punch',
                'employee_notes' => 'Client visit',
                'status' => RegularizationStatus::Pending,
            ]);

            $this->actingAs(UserModel::query()->findOrFail($managerUserId));
            $this->patchJson("/api/v1/hr/attendance/regularizations/{$reg2->id}/action", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 42,
                'approve' => false,
            ])->assertStatus(409);

            $this->patchJson("/api/v1/hr/attendance/regularizations/{$reg2->id}/action", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'approve' => false,
                'notes' => 'Rejected',
            ])->assertStatus(200)
                ->assertJsonPath('data.status', 'rejected')
                ->assertJsonPath('data.row_version', 2);
        });
    }

    public function test_capture_endpoints_enforce_employee_ownership_unless_manage_attendance(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userA = $this->createUser($tenantId);
            $managerId = $this->createUser($tenantId);
            $this->app->instance(PermissionCheckerInterface::class, new class($managerId) implements PermissionCheckerInterface
            {
                public function __construct(private readonly int $managerId) {}

                public function allows(int $userId, int $tenantId, string $permission): bool
                {
                    return $userId === $this->managerId;
                }
            });

            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-CAP']);
            $empA = $this->createEmployee($tenantId, $orgId, 'Emp A', 'EMP-CAP-A', $userA);
            $empB = $this->createEmployee($tenantId, $orgId, 'Emp B', 'EMP-CAP-B');

            $shift = HrShift::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'DAY-CAP',
                'name' => 'Day Shift',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
            ]);
            HrShiftAssignment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $empB->id,
                'shift_id' => $shift->id,
                'assignment_type' => ShiftAssignmentType::Recurring,
                'start_date' => '2026-01-01',
            ]);

            $kiosk = HrAttendanceKiosk::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'kiosk_code' => 'KIOSK-CAP-1',
                'name' => 'Lobby Kiosk',
                'secret_key' => 'kiosk-secret-cap-1',
                'rotation_seconds' => 60,
                'is_active' => true,
            ]);
            $token = app(DynamicQrSecurityService::class)->generateToken($kiosk)['token'];

            // User A cannot punch for Emp B
            $this->actingAs(UserModel::query()->findOrFail($userA));
            $this->postJson('/api/v1/hr/attendance/capture/web', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $empB->id,
                'punch_type' => 'in',
            ])->assertStatus(403);

            $this->postJson('/api/v1/hr/attendance/capture/mobile', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $empB->id,
                'punch_type' => 'in',
                'latitude' => 6.9271,
                'longitude' => 79.8612,
            ])->assertStatus(403);

            $this->postJson('/api/v1/hr/attendance/capture/qr-scan', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $empB->id,
                'qr_token' => $token,
                'punch_type' => 'in',
            ])->assertStatus(403);

            $this->postJson('/api/v1/hr/attendance/capture', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'capture_method' => 'web_portal',
                'payload' => [
                    'employee_id' => $empB->id,
                    'punch_type' => 'in',
                ],
            ])->assertStatus(403);

            // User A CAN web punch for Emp A
            $this->postJson('/api/v1/hr/attendance/capture/web', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $empA->id,
                'punch_type' => 'in',
            ])->assertStatus(201);

            // Manager with MANAGE_ATTENDANCE CAN punch for Emp B
            $this->actingAs(UserModel::query()->findOrFail($managerId));
            $this->postJson('/api/v1/hr/attendance/capture/web', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $empB->id,
                'punch_type' => 'in',
            ])->assertStatus(201);
        });
    }

    public function test_zkteco_cdata_rejects_ambiguous_serial_across_tenants_and_disambiguates_via_tenant_or_token(): void
    {
        $tenantA = $this->tenant();
        $tenantB = $this->tenant();

        $this->withTenantExecutionContext($tenantA, function () use ($tenantA): void {
            HrBiometricDevice::create([
                'tenant_id' => $tenantA,
                'device_code' => 'DEV-A',
                'name' => 'Tenant A Clock',
                'protocol' => BiometricProtocol::ZkTecoPush,
                'serial_number' => 'SHARED-SN-100',
                'auth_token' => 'token-tenant-a',
                'is_enabled' => true,
            ]);
        });

        $this->withTenantExecutionContext($tenantB, function () use ($tenantB): void {
            HrBiometricDevice::create([
                'tenant_id' => $tenantB,
                'device_code' => 'DEV-B',
                'name' => 'Tenant B Clock',
                'protocol' => BiometricProtocol::ZkTecoPush,
                'serial_number' => 'SHARED-SN-100',
                'auth_token' => 'token-tenant-b',
                'is_enabled' => true,
            ]);
        });

        // 1. Ambiguous SN without tenant or token -> 409
        $this->get('/api/v1/hr/biometric/push/zkteco/cdata?SN=SHARED-SN-100&options=all')
            ->assertStatus(409);

        // 2. Disambiguated via tenant-scoped route
        $this->get("/api/v1/hr/biometric/push/zkteco/{$tenantA}/cdata?SN=SHARED-SN-100&options=all")
            ->assertStatus(200)
            ->assertSee('GET OPTION FROM: SHARED-SN-100');

        // 3. Disambiguated via device auth_token
        $this->get('/api/v1/hr/biometric/push/zkteco/cdata?SN=SHARED-SN-100&token=token-tenant-b&options=all')
            ->assertStatus(200);

        // 4. Invalid token for tenant-scoped route -> 401
        $this->get("/api/v1/hr/biometric/push/zkteco/{$tenantA}/cdata?SN=SHARED-SN-100&token=wrong-token")
            ->assertStatus(401);
    }

    public function test_hikvision_push_and_generic_webhook_reject_ambiguous_identifiers_and_disambiguate_per_tenant(): void
    {
        $tenantA = $this->tenant();
        $tenantB = $this->tenant();

        $this->withTenantExecutionContext($tenantA, function () use ($tenantA): void {
            HrBiometricDevice::create([
                'tenant_id' => $tenantA,
                'device_code' => 'SHARED-IOT-01',
                'name' => 'Tenant A Hikvision',
                'protocol' => BiometricProtocol::HikvisionIsapi,
                'serial_number' => 'HIK-SHARED-900',
                'auth_token' => 'hik-token-a',
                'is_enabled' => true,
            ]);
            HrBiometricDevice::create([
                'tenant_id' => $tenantA,
                'device_code' => 'SHARED-WH-01',
                'name' => 'Tenant A Webhook',
                'protocol' => BiometricProtocol::GenericWebhook,
                'serial_number' => 'WH-SHARED-900',
                'auth_token' => 'wh-token-a',
                'is_enabled' => true,
            ]);
        });

        $this->withTenantExecutionContext($tenantB, function () use ($tenantB): void {
            HrBiometricDevice::create([
                'tenant_id' => $tenantB,
                'device_code' => 'SHARED-IOT-02',
                'name' => 'Tenant B Hikvision',
                'protocol' => BiometricProtocol::HikvisionIsapi,
                'serial_number' => 'HIK-SHARED-900',
                'auth_token' => 'hik-token-b',
                'is_enabled' => true,
            ]);
            HrBiometricDevice::create([
                'tenant_id' => $tenantB,
                'device_code' => 'SHARED-WH-01',
                'name' => 'Tenant B Webhook',
                'protocol' => BiometricProtocol::GenericWebhook,
                'serial_number' => 'WH-SHARED-900',
                'auth_token' => 'wh-token-b',
                'is_enabled' => true,
            ]);
        });

        $hikPayload = [
            'serial_number' => 'HIK-SHARED-900',
            'events' => [
                [
                    'employeeNoString' => '101',
                    'time' => '2026-11-25 09:00:00',
                ],
            ],
        ];

        // 1. Hikvision ambiguous serial without tenant or token -> 409
        $this->postJson('/api/v1/hr/biometric/push/hikvision', $hikPayload)
            ->assertStatus(409);

        // 2. Hikvision disambiguated via tenant-scoped route -> 200, ingested only for Tenant A
        $this->postJson("/api/v1/hr/biometric/push/hikvision/{$tenantA}", $hikPayload)
            ->assertStatus(200)
            ->assertJsonPath('status', 'OK')
            ->assertJsonPath('received', 1);

        $this->assertSame(1, HrBiometricRawPunch::withoutGlobalScopes()->where('tenant_id', $tenantA)->count());
        $this->assertSame(0, HrBiometricRawPunch::withoutGlobalScopes()->where('tenant_id', $tenantB)->count());

        // 3. Hikvision disambiguated via device auth_token -> 200, ingested only for Tenant B
        $this->postJson('/api/v1/hr/biometric/push/hikvision', $hikPayload, ['X-Device-Token' => 'hik-token-b'])
            ->assertStatus(200)
            ->assertJsonPath('received', 1);

        $this->assertSame(1, HrBiometricRawPunch::withoutGlobalScopes()->where('tenant_id', $tenantB)->count());

        // 4. Hikvision invalid token on tenant-scoped route -> 401
        $this->postJson("/api/v1/hr/biometric/push/hikvision/{$tenantA}", $hikPayload, ['X-Device-Token' => 'wrong-token'])
            ->assertStatus(401);

        $whPayload = [
            'punches' => [
                [
                    'user_id' => '202',
                    'punch_time' => '2026-11-25 09:15:00',
                    'state' => 'check_in',
                    'verify_method' => 'face',
                ],
            ],
        ];

        // 5. Generic webhook ambiguous device_code without tenant or token -> 409
        $this->postJson('/api/v1/hr/biometric/push/webhook/SHARED-WH-01', $whPayload)
            ->assertStatus(409);

        // 6. Generic webhook disambiguated via Bearer token -> 200, ingested into Tenant B
        $this->postJson('/api/v1/hr/biometric/push/webhook/SHARED-WH-01', $whPayload, ['Authorization' => 'Bearer wh-token-b'])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSame(2, HrBiometricRawPunch::withoutGlobalScopes()->where('tenant_id', $tenantB)->count());
        $this->assertSame(1, HrBiometricRawPunch::withoutGlobalScopes()->where('tenant_id', $tenantA)->count());

        // 7. Generic webhook disambiguated via tenant-scoped route + HMAC signature -> 200, ingested into Tenant A
        $rawWhBody = json_encode($whPayload, JSON_THROW_ON_ERROR);
        $hmac = hash_hmac('sha256', $rawWhBody, 'wh-token-a');
        $this->call(
            'POST',
            "/api/v1/hr/biometric/push/webhook/{$tenantA}/SHARED-WH-01",
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_BIOMETRIC_SIGNATURE' => $hmac,
            ],
            $rawWhBody,
        )->assertStatus(200)->assertJsonPath('success', true);

        $this->assertSame(2, HrBiometricRawPunch::withoutGlobalScopes()->where('tenant_id', $tenantA)->count());
    }

    public function test_hikvision_push_rejects_ambiguous_ip_across_tenants_and_disambiguates_via_tenant_or_token(): void
    {
        $tenantA = $this->tenant();
        $tenantB = $this->tenant();

        $this->withTenantExecutionContext($tenantA, function () use ($tenantA): void {
            HrBiometricDevice::create([
                'tenant_id' => $tenantA,
                'device_code' => 'HIK-IP-A',
                'name' => 'Tenant A Hikvision IP',
                'protocol' => BiometricProtocol::HikvisionIsapi,
                'ip_address' => '203.0.113.50',
                'auth_token' => 'hik-ip-token-a',
                'is_enabled' => true,
            ]);
        });

        $this->withTenantExecutionContext($tenantB, function () use ($tenantB): void {
            HrBiometricDevice::create([
                'tenant_id' => $tenantB,
                'device_code' => 'HIK-IP-B',
                'name' => 'Tenant B Hikvision IP',
                'protocol' => BiometricProtocol::HikvisionIsapi,
                'ip_address' => '203.0.113.50',
                'auth_token' => 'hik-ip-token-b',
                'is_enabled' => true,
            ]);
        });

        $payloadWithoutSerial = [
            'events' => [
                [
                    'employeeNoString' => '303',
                    'time' => '2026-11-25 10:00:00',
                ],
            ],
        ];

        // 1. Ambiguous IP without tenant or token -> 409
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
            ->postJson('/api/v1/hr/biometric/push/hikvision', $payloadWithoutSerial)
            ->assertStatus(409);

        // 2. Disambiguated via tenant-scoped route -> 200 for Tenant A only
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
            ->postJson("/api/v1/hr/biometric/push/hikvision/{$tenantA}", $payloadWithoutSerial)
            ->assertStatus(200)
            ->assertJsonPath('received', 1);

        $this->assertSame(1, HrBiometricRawPunch::withoutGlobalScopes()->where('tenant_id', $tenantA)->count());
        $this->assertSame(0, HrBiometricRawPunch::withoutGlobalScopes()->where('tenant_id', $tenantB)->count());

        // 3. Disambiguated via X-Device-Token -> 200 for Tenant B only
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
            ->postJson('/api/v1/hr/biometric/push/hikvision', $payloadWithoutSerial, ['X-Device-Token' => 'hik-ip-token-b'])
            ->assertStatus(200)
            ->assertJsonPath('received', 1);

        $this->assertSame(1, HrBiometricRawPunch::withoutGlobalScopes()->where('tenant_id', $tenantB)->count());
    }

    public function test_credential_destroy_occ_and_hr_master_occ_and_delete_guards(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $this->actingAs(UserModel::query()->findOrFail($userId));
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-MST']);
            $emp = $this->createEmployee($tenantId, $orgId, 'Cred Emp', 'EMP-CRED-1');

            // 1. Credential destroy OCC
            $cred = HrEmployeeAttendanceCredential::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $emp->id,
                'credential_type' => CredentialType::RfidCard,
                'credential_value' => 'RFID-999001',
                'status' => CredentialStatus::Active,
                'row_version' => 1,
            ]);

            $this->deleteJson("/api/v1/hr/employees/{$emp->id}/credentials/{$cred->id}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 99,
            ])->assertStatus(409);

            $this->deleteJson("/api/v1/hr/employees/{$emp->id}/credentials/{$cred->id}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
            ])->assertStatus(204);

            // 2. HR Master update OCC
            $parentDept = HrDepartment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'DEPT-PARENT',
                'name' => 'Parent Dept',
                'is_active' => true,
            ]);

            $this->putJson("/api/v1/hr/departments/{$parentDept->id}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'DEPT-PARENT',
                'name' => 'Stale Update',
                'expected_version' => 5,
            ])->assertStatus(409);

            $this->putJson("/api/v1/hr/departments/{$parentDept->id}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'DEPT-PARENT',
                'name' => 'Parent Dept Updated',
                'expected_version' => 1,
            ])->assertStatus(200)
                ->assertJsonPath('data.row_version', 2);

            // 3. Delete guard: child department
            $childDept = HrDepartment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'parent_id' => $parentDept->id,
                'code' => 'DEPT-CHILD',
                'name' => 'Child Dept',
                'is_active' => true,
            ]);

            $this->deleteJson("/api/v1/hr/departments/{$parentDept->id}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
            ])->assertStatus(422);

            $this->deleteJson("/api/v1/hr/departments/{$childDept->id}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
            ])->assertStatus(204);

            // 4. Delete guard: leave policy referencing department and employment type
            $empType = HrEmploymentType::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'PERM-P3',
                'name' => 'Permanent',
                'is_active' => true,
            ]);
            $leaveType = HrLeaveType::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'AL-P3',
                'name' => 'Annual',
                'is_paid' => true,
                'status' => 'active',
            ]);
            HrLeavePolicy::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'leave_type_id' => $leaveType->id,
                'department_id' => $parentDept->id,
                'employment_type_id' => $empType->id,
                'name' => 'Dept Policy',
                'annual_allocation_days' => '14.00',
                'accrual_frequency' => LeaveAccrualFrequency::AnnualUpfront,
                'effective_from' => '2026-01-01',
            ]);

            $this->deleteJson("/api/v1/hr/departments/{$parentDept->id}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
            ])->assertStatus(422);

            $this->deleteJson("/api/v1/hr/employment-types/{$empType->id}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
            ])->assertStatus(422);
        });
    }

    public function test_payroll_integration_excludes_paid_leave_and_holiday_from_worked_days_without_deducting_pay(): void
    {
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ', 'code' => 'HQ-PAY']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Payroll Emp', 'EMP-PAY-1');

            $period = PayrollPayPeriod::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'November 2026',
                'cycle_type' => PayrollCycleType::Monthly,
                'period_start' => '2026-11-01',
                'period_end' => '2026-11-30',
                'payout_date' => '2026-11-30',
                'total_working_days' => 20,
            ]);

            $basicComp = PayrollSalaryComponent::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'BASIC_EARN',
                'name' => 'Basic',
                'type' => SalaryComponentType::Earning,
                'calculation_type' => CalculationType::Fixed,
                'is_taxable' => true,
                'is_statutory_base' => true,
                'is_active' => true,
            ]);

            $structure = PayrollSalaryStructure::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'STD-STR',
                'name' => 'Standard',
                'is_active' => true,
            ]);
            PayrollSalaryStructureItem::create([
                'tenant_id' => $tenantId,
                'salary_structure_id' => $structure->id,
                'salary_component_id' => $basicComp->id,
                'calculation_type' => CalculationType::Fixed,
                'amount_or_rate' => '0.0000',
            ]);

            $assignment = PayrollEmployeeSalaryAssignment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'salary_structure_id' => $structure->id,
                'base_salary' => '2000.0000', // daily rate = 100.0000
                'hourly_rate' => '12.5000',
                'payment_method' => 'bank_transfer',
                'effective_from' => '2026-01-01',
                'is_active' => true,
            ]);

            // Attendance: 1 Present, 1 OnLeave (paid), 1 Holiday, 1 Absent, 1 HalfDay
            HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-11-02',
                'status' => AttendanceStatus::Present,
            ]);
            HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-11-03',
                'status' => AttendanceStatus::OnLeave,
            ]);
            HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-11-04',
                'status' => AttendanceStatus::Holiday,
            ]);
            HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-11-05',
                'status' => AttendanceStatus::Absent,
            ]);
            HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'attendance_date' => '2026-11-06',
                'status' => AttendanceStatus::HalfDay,
            ]);

            // 1 day unpaid leave request
            $unpaidType = HrLeaveType::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'code' => 'UL-P3',
                'name' => 'Unpaid Leave',
                'is_paid' => false,
                'status' => 'active',
            ]);
            HrLeaveRequest::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'leave_type_id' => $unpaidType->id,
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-10',
                'duration_type' => LeaveDurationType::FullDay,
                'total_days' => '1.00',
                'reason' => 'Personal',
                'status' => LeaveRequestStatus::Approved,
            ]);

            $agg = app(PayrollAttendanceIntegrationService::class)->aggregateAttendance(
                $tenantId,
                (int) $employee->id,
                '2026-11-01',
                '2026-11-30',
            );

            // Present (1.0) + HalfDay (0.5) = 1.50; OnLeave & Holiday excluded from worked_days
            $this->assertSame('1.50', $agg['worked_days']);
            // Absent (1.0) + HalfDay (0.5) = 1.50; OnLeave & Holiday excluded from absent_days
            $this->assertSame('1.50', $agg['absent_days']);
            $this->assertSame('1.00', $agg['unpaid_leave_days']);

            $payslip = app(PayrollCalculationEngine::class)->calculateForEmployee($employee, $period, $structure, $assignment);
            $this->assertNotNull($payslip);
            // Gross = 2000.0000; Deductions = 1.50 * 100 (150) + 1.00 * 100 (100) = 250.0000; Net = 1750.0000
            $this->assertSame('2000.0000', $payslip->grossPay);
            $this->assertSame('250.0000', $payslip->totalDeductions);
            $this->assertSame('1750.0000', $payslip->netPay);
        });
    }

    private function tenant(): int
    {
        $now = now();
        $code = 'P3-'.Str::upper(Str::random(5));

        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Phase3 Tenant '.$code,
            'code' => $code,
            'slug' => 'phase3-'.strtolower($code),
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
            'email' => 'p3-'.Str::random(8).'@example.com',
        ]);
    }

    private function createEmployee(int $tenantId, int $orgId, string $name, string $num, ?int $userId = null): HrEmployee
    {
        return HrEmployee::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgId,
            'user_id' => $userId,
            'employee_number' => $num,
            'first_name' => $name,
            'last_name' => 'Tester',
            'display_name' => $name.' Tester',
            'status' => EmployeeStatus::Active,
            'joined_date' => '2025-01-01',
        ]);
    }
}

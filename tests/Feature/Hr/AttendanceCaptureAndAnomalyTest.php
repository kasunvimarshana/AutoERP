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

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Hr\DTOs\PunchData;
use Modules\Hr\Enums\AnomalyType;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Enums\CredentialStatus;
use Modules\Hr\Enums\CredentialType;
use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Models\HrAttendanceGeofence;
use Modules\Hr\Models\HrAttendanceKiosk;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeAttendanceCredential;
use Modules\Hr\Models\HrShift;
use Modules\Hr\Models\HrShiftAssignment;
use Modules\Hr\Services\AttendanceAnomalyService;
use Modules\Hr\Services\AttendanceCalculationEngine;
use Modules\Hr\Services\Capture\AttendanceCaptureService;
use Modules\Hr\Services\Capture\BatchAttendanceImportService;
use Modules\Hr\Services\Capture\DynamicQrSecurityService;
use Modules\User\Models\UserModel;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class AttendanceCaptureAndAnomalyTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_punch_clock_records_valid_punch_and_calculates_daily_record(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ Branch', 'code' => 'HQ-01']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Nuwan Bandara', 'EMP-7701');
            $shift = $this->createShift($tenantId, $orgId);
            $this->assignShift($tenantId, $orgId, $employee->id, $shift->id);

            $captureService = app(AttendanceCaptureService::class);

            $punch = $captureService->recordWebPunch(
                tenantId: $tenantId,
                employeeId: (int) $employee->id,
                punchType: PunchType::In,
                ipAddress: '192.168.1.50',
                notes: 'Clocked in from workstation',
            );

            self::assertInstanceOf(HrAttendancePunch::class, $punch);
            self::assertSame(PunchType::In, $punch->punch_type);
            self::assertSame(CaptureMethod::WebPortal, $punch->capture_method);
            self::assertSame(PunchSource::Web, $punch->punch_source);
            self::assertNotNull($punch->attendance_record_id);

            $record = HrAttendanceRecord::find($punch->attendance_record_id);
            self::assertNotNull($record);
            self::assertSame((int) $employee->id, (int) $record->employee_id);
        });
    }

    public function test_mobile_gps_punch_validates_geofence_and_radius(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ Branch', 'code' => 'HQ-02']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Nuwan Bandara', 'EMP-7702');
            $shift = $this->createShift($tenantId, $orgId);
            $this->assignShift($tenantId, $orgId, $employee->id, $shift->id);

            $captureService = app(AttendanceCaptureService::class);

            // Branch geofence at (6.9271, 79.8612) with 100m radius
            $geofence = HrAttendanceGeofence::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'Colombo HQ',
                'code' => 'HQ-COLOMBO',
                'latitude' => '6.9271000',
                'longitude' => '79.8612000',
                'radius_meters' => 100,
                'is_active' => true,
            ]);

            // Punch at inside radius
            $validPunch = $captureService->recordMobilePunch(
                tenantId: $tenantId,
                employeeId: (int) $employee->id,
                punchType: PunchType::In,
                latitude: 6.9272,
                longitude: 79.8613,
                accuracyMeters: 5,
                deviceUuid: 'iPhone-UUID-12345',
                geofenceId: (int) $geofence->id,
            );

            self::assertSame(CaptureMethod::MobileGps, $validPunch->capture_method);
            self::assertSame((int) $geofence->id, (int) $validPunch->geofence_id);

            // Punch 5000m away (outside radius) must throw exception
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('violate geofence');

            $captureService->recordMobilePunch(
                tenantId: $tenantId,
                employeeId: (int) $employee->id,
                punchType: PunchType::Out,
                latitude: 6.9500,
                longitude: 79.9000,
                accuracyMeters: 5,
                geofenceId: (int) $geofence->id,
            );
        });
    }

    public function test_rfid_smart_card_tap_resolves_active_credential(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ Branch', 'code' => 'HQ-03']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Nuwan Bandara', 'EMP-7703');
            $shift = $this->createShift($tenantId, $orgId);
            $this->assignShift($tenantId, $orgId, $employee->id, $shift->id);

            $captureService = app(AttendanceCaptureService::class);

            $cardUid = 'E280689400004012';
            $credential = HrEmployeeAttendanceCredential::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'credential_type' => CredentialType::RfidCard,
                'credential_value' => $cardUid,
                'label' => 'Mifare Badge 01',
                'status' => CredentialStatus::Active,
            ]);

            $punch = $captureService->recordRfidPunch(
                tenantId: $tenantId,
                cardUid: $cardUid,
                punchType: PunchType::In,
                deviceId: 'RFID-READER-DOOR-A',
            );

            self::assertSame((int) $employee->id, (int) $punch->employee_id);
            self::assertSame(CaptureMethod::RfidCard, $punch->capture_method);
            self::assertSame((int) $credential->id, (int) $punch->credential_id);

            // Unknown card must reject
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Unrecognized rfid_card credential');
            $captureService->recordRfidPunch($tenantId, 'UNKNOWN-CARD-999', PunchType::In);
        });
    }

    public function test_dynamic_rotating_qr_lifecycle_and_replay_protection(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ Branch', 'code' => 'HQ-04']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Nuwan Bandara', 'EMP-7704');
            $shift = $this->createShift($tenantId, $orgId);
            $this->assignShift($tenantId, $orgId, $employee->id, $shift->id);

            $qrSecurityService = app(DynamicQrSecurityService::class);
            $captureService = app(AttendanceCaptureService::class);

            $kiosk = HrAttendanceKiosk::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'kiosk_code' => 'KIOSK-LOBBY-01',
                'name' => 'Lobby Entrance Kiosk',
                'secret_key' => 'super-secret-hmac-key-lobby-1234',
                'rotation_seconds' => 20,
                'is_active' => true,
            ]);

            // Generate dynamic token
            $tokenData = $qrSecurityService->generateToken($kiosk);
            self::assertNotEmpty($tokenData['token']);
            self::assertGreaterThan(0, $tokenData['expires_in_seconds']);

            // Verify token validates
            $isValid = $qrSecurityService->verifyToken($kiosk, $tokenData['token']);
            self::assertTrue($isValid);

            // Record scan
            $punch = $captureService->recordKioskQrPunch(
                tenantId: $tenantId,
                employeeId: (int) $employee->id,
                qrToken: $tokenData['token'],
                punchType: PunchType::In,
            );

            self::assertSame(CaptureMethod::TotpQr, $punch->capture_method);
            self::assertSame('KIOSK-LOBBY-01', $punch->device_id);

            // Forged signature must be rejected
            $this->expectException(\InvalidArgumentException::class);
            $fakeToken = base64_encode("{$kiosk->id}:999999:bad-signature");
            $captureService->recordKioskQrPunch($tenantId, (int) $employee->id, $fakeToken, PunchType::In);
        });
    }

    public function test_batch_csv_import_two_phase_preview_and_commit(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ Branch', 'code' => 'HQ-05']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Nuwan Bandara', 'EMP-7705');
            $shift = $this->createShift($tenantId, $orgId);
            $this->assignShift($tenantId, $orgId, $employee->id, $shift->id);

            $importService = app(BatchAttendanceImportService::class);

            $csv = "employee_code,date,time,punch_type,notes\n"
                ."EMP-7705,2026-11-20,08:02:00,in,Morning clock-in\n"
                ."EMP-7705,2026-11-20,17:05:00,out,Evening clock-out\n";

            // Phase 1: Preview
            $preview = $importService->preview($tenantId, $csv);
            self::assertSame(2, $preview['total_rows']);
            self::assertSame(2, $preview['valid_rows']);
            self::assertSame(0, $preview['invalid_rows']);

            $userId = $this->createUser($tenantId);

            // Phase 2: Commit
            $batch = $importService->commit(
                tenantId: $tenantId,
                userId: $userId,
                fileName: 'nov20_attendance.csv',
                csvContent: $csv,
            );

            self::assertSame(2, (int) $batch->processed_rows);
            self::assertSame('imported', $batch->status->value);

            // Assert attendance record was recalculated
            $record = HrAttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', '2026-11-20')
                ->first();

            self::assertNotNull($record);
            self::assertSame(AttendanceStatus::Present, $record->status);
            self::assertGreaterThan(0, (int) $record->total_worked_minutes);
        });
    }

    public function test_missing_punch_anomaly_detection_and_admin_quick_resolution(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ Branch', 'code' => 'HQ-06']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Nuwan Bandara', 'EMP-7706');
            $shift = $this->createShift($tenantId, $orgId);
            $this->assignShift($tenantId, $orgId, $employee->id, $shift->id);

            $engine = app(AttendanceCalculationEngine::class);
            $anomalyService = app(AttendanceAnomalyService::class);

            // Create single IN punch for yesterday
            $yesterday = CarbonImmutable::yesterday()->toDateString();
            $engine->recordPunch($tenantId, new PunchData(
                employeeId: (int) $employee->id,
                punchType: PunchType::In,
                punchTime: "{$yesterday} 08:00:00",
                punchSource: PunchSource::BiometricDevice,
                captureMethod: CaptureMethod::Biometric,
            ));

            // Scan anomalies
            $scanResult = $anomalyService->scanAnomalies($tenantId, $yesterday);
            self::assertGreaterThanOrEqual(1, $scanResult['anomalies_detected']);

            $record = HrAttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $yesterday)
                ->firstOrFail();

            self::assertSame(AttendanceStatus::MissingPunch, $record->status);
            self::assertSame(AnomalyType::MissingOut, $record->anomaly_type);
            self::assertTrue($record->requires_regularization);

            $userId = $this->createUser($tenantId);

            // Admin quick-resolves with OUT punch at 17:00:00
            $resolved = $anomalyService->quickResolveAnomaly(
                record: $record,
                resolvedByUserId: $userId,
                inTime: "{$yesterday} 08:00:00",
                outTime: "{$yesterday} 17:00:00",
                resolutionReason: 'Employee forgot to punch out due to client emergency',
            );

            self::assertNull($resolved->anomaly_type);
            self::assertFalse($resolved->requires_regularization);
            self::assertTrue($resolved->is_regularized);
            self::assertSame(AttendanceStatus::Present, $resolved->status);
        });
    }

    public function test_http_api_endpoints_for_all_capture_channels_anomalies_and_credentials(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $user = UserModel::query()->findOrFail($userId);
            $this->actingAs($user);

            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ Branch', 'code' => 'HQ-07']);
            $employee = $this->createEmployee($tenantId, $orgId, 'Nuwan Bandara', 'EMP-7707');
            $shift = $this->createShift($tenantId, $orgId);
            $this->assignShift($tenantId, $orgId, $employee->id, $shift->id);

            // 1. Web punch via HTTP
            $webRes = $this->postJson('/api/v1/hr/attendance/capture/web', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'punch_type' => 'in',
                'notes' => 'HTTP Web Punch',
            ]);
            $webRes->assertStatus(201)
                ->assertJsonPath('data.capture_method', 'web_portal')
                ->assertJsonPath('data.punch_type', 'in');

            // 2. Issue RFID Credential via HTTP
            $credRes = $this->postJson("/api/v1/hr/employees/{$employee->id}/credentials", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'credential_type' => 'rfid_card',
                'credential_value' => 'HTTP-CARD-9901',
                'label' => 'HTTP Mifare Badge',
            ]);
            $credRes->assertStatus(201)
                ->assertJsonPath('data.credential_value', 'HTTP-CARD-9901');
            $credId = (int) $credRes->json('data.id');

            // 3. List Credentials via HTTP
            $this->getJson("/api/v1/hr/employees/{$employee->id}/credentials?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200)
                ->assertJsonCount(1, 'data');

            // 4. RFID punch via HTTP
            $this->postJson('/api/v1/hr/attendance/capture/rfid', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'card_uid' => 'HTTP-CARD-9901',
                'punch_type' => 'break_out',
            ])->assertStatus(201)->assertJsonPath('data.capture_method', 'rfid_card');

            // 5. Suspend Credential via HTTP
            $this->patchJson("/api/v1/hr/employees/{$employee->id}/credentials/{$credId}/status", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'status' => 'suspended',
            ])->assertStatus(200)->assertJsonPath('data.status', 'suspended');

            // 6. Create Geofence & Kiosk via HTTP
            $geoRes = $this->postJson('/api/v1/hr/attendance/geofences', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'Main Gate Geofence',
                'code' => 'GEO-MAIN',
                'latitude' => 6.9271,
                'longitude' => 79.8612,
                'radius_meters' => 150,
            ])->assertStatus(201)->assertJsonPath('data.code', 'GEO-MAIN');
            $geoId = (int) $geoRes->json('data.id');

            $kioskRes = $this->postJson('/api/v1/hr/attendance/kiosks', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'kiosk_code' => 'KIOSK-HTTP-1',
                'name' => 'Front Desk Terminal',
                'geofence_id' => $geoId,
                'rotation_seconds' => 20,
            ])->assertStatus(201)->assertJsonPath('data.kiosk_code', 'KIOSK-HTTP-1');
            $kioskId = (int) $kioskRes->json('data.id');

            // 7. Get Dynamic QR Token via HTTP
            $qrTokenRes = $this->getJson("/api/v1/hr/attendance/capture/kiosks/{$kioskId}/qr-token?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200);
            $token = (string) $qrTokenRes->json('data.token');
            self::assertNotEmpty($token);

            // 8. Scan QR Token via HTTP
            $this->postJson('/api/v1/hr/attendance/capture/qr-scan', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => $employee->id,
                'qr_token' => $token,
                'punch_type' => 'break_in',
                'latitude' => 6.9272,
                'longitude' => 79.8613,
            ])->assertStatus(201)->assertJsonPath('data.capture_method', 'totp_qr');

            // 9. Batch Import Preview via HTTP
            $csv = "employee_code,date,time,punch_type\nEMP-7707,2026-11-21,08:00:00,in\nEMP-7707,2026-11-21,17:00:00,out\n";
            $this->postJson('/api/v1/hr/attendance/capture/import/preview', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'csv_content' => $csv,
            ])->assertStatus(200)->assertJsonPath('data.valid_rows', 2);

            // 10. Batch Import Commit via HTTP
            $userId = $this->createUser($tenantId);
            $this->postJson('/api/v1/hr/attendance/capture/import/commit', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'user_id' => $userId,
                'file_name' => 'http_import.csv',
                'csv_content' => $csv,
            ])->assertStatus(201)->assertJsonPath('data.status', 'imported');

            // 11. Anomaly scan & list via HTTP
            $this->postJson('/api/v1/hr/attendance/anomalies/scan', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'date' => '2026-11-21',
            ])->assertStatus(200);

            $this->getJson("/api/v1/hr/attendance/anomalies?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200);
        });
    }

    public function test_kiosk_secret_key_hidden_cross_tenant_geofence_rejected_occ_and_partial_update_preserved(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();
        $otherTenantId = $this->tenant();

        $otherGeofenceId = $this->withTenantExecutionContext($otherTenantId, function () use ($otherTenantId): int {
            $otherOrgId = OrganizationUnitFixture::create(['tenant_id' => $otherTenantId, 'name' => 'Other Branch', 'code' => 'OTH-01']);

            return (int) HrAttendanceGeofence::create([
                'tenant_id' => $otherTenantId,
                'organization_unit_id' => $otherOrgId,
                'name' => 'Other Tenant Fence',
                'code' => 'OTH-GEO',
                'latitude' => 6.9,
                'longitude' => 79.8,
                'radius_meters' => 100,
                'is_active' => true,
            ])->id;
        });

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $otherGeofenceId): void {
            $userId = $this->createUser($tenantId);
            $user = UserModel::query()->findOrFail($userId);
            $this->actingAs($user);
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ Branch', 'code' => 'HQ-SEC']);

            // Create geofence with optional fields
            $geoRes = $this->postJson('/api/v1/hr/attendance/geofences', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'name' => 'HQ Fence',
                'code' => 'HQ-GEO',
                'latitude' => 6.9271,
                'longitude' => 79.8612,
                'radius_meters' => 150,
                'ip_whitelist' => '10.0.0.0/8',
                'notes' => 'Keep me',
                'is_active' => false,
            ])->assertStatus(201);
            $geoId = (int) $geoRes->json('data.id');

            // Partial update omitting optional fields preserves ip_whitelist, notes, is_active
            $updatedGeo = $this->putJson("/api/v1/hr/attendance/geofences/{$geoId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'name' => 'HQ Fence Renamed',
                'code' => 'HQ-GEO',
                'latitude' => 6.9271,
                'longitude' => 79.8612,
                'radius_meters' => 200,
            ])->assertStatus(200);
            self::assertSame('10.0.0.0/8', $updatedGeo->json('data.ip_whitelist'));
            self::assertSame('Keep me', $updatedGeo->json('data.notes'));
            self::assertFalse((bool) $updatedGeo->json('data.is_active'));
            self::assertSame(2, (int) $updatedGeo->json('data.row_version'));

            // Explicit is_active = true followed by is_active = null preserves true
            $this->putJson("/api/v1/hr/attendance/geofences/{$geoId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 2,
                'name' => 'HQ Fence Renamed',
                'code' => 'HQ-GEO',
                'latitude' => 6.9271,
                'longitude' => 79.8612,
                'radius_meters' => 200,
                'is_active' => true,
            ])->assertStatus(200);

            $nullActiveGeo = $this->putJson("/api/v1/hr/attendance/geofences/{$geoId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 3,
                'name' => 'HQ Fence Renamed',
                'code' => 'HQ-GEO',
                'latitude' => 6.9271,
                'longitude' => 79.8612,
                'radius_meters' => 200,
                'is_active' => null,
            ])->assertStatus(200);
            self::assertTrue((bool) $nullActiveGeo->json('data.is_active'));
            self::assertSame(4, (int) $nullActiveGeo->json('data.row_version'));

            // Stale expected_version returns 409
            $this->putJson("/api/v1/hr/attendance/geofences/{$geoId}", [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'expected_version' => 1,
                'name' => 'Stale',
                'code' => 'HQ-GEO',
                'latitude' => 6.9271,
                'longitude' => 79.8612,
                'radius_meters' => 200,
            ])->assertStatus(409);

            // Cross-tenant geofence_id is rejected on kiosk store
            $this->postJson('/api/v1/hr/attendance/kiosks', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'kiosk_code' => 'KIOSK-X',
                'name' => 'Cross Tenant Kiosk',
                'geofence_id' => $otherGeofenceId,
            ])->assertStatus(422)->assertJsonValidationErrors(['geofence_id']);

            // Valid kiosk never leaks secret_key
            $kioskRes = $this->postJson('/api/v1/hr/attendance/kiosks', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'kiosk_code' => 'KIOSK-SEC',
                'name' => 'Secure Kiosk',
                'geofence_id' => $geoId,
            ])->assertStatus(201);
            self::assertArrayNotHasKey('secret_key', $kioskRes->json('data'));

            $kioskList = $this->getJson("/api/v1/hr/attendance/kiosks?tenant_id={$tenantId}&organization_unit_id={$orgId}")
                ->assertStatus(200);
            self::assertArrayNotHasKey('secret_key', $kioskList->json('data.0'));

            // Invalid enum returns 422 instead of 500
            $this->postJson('/api/v1/hr/attendance/capture/web', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
                'employee_id' => 1,
                'punch_type' => 'not_a_valid_punch_type',
            ])->assertStatus(422)->assertJsonValidationErrors(['punch_type']);

            // Invalid import payload returns 422
            $this->postJson('/api/v1/hr/attendance/capture/import/preview', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgId,
            ])->assertStatus(422);
        });
    }

    public function test_roster_endpoint_batches_queries_without_n_plus_one(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $tenantId = $this->tenant();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $userId = $this->createUser($tenantId);
            $user = UserModel::query()->findOrFail($userId);
            $this->actingAs($user);
            $orgId = OrganizationUnitFixture::create(['tenant_id' => $tenantId, 'name' => 'HQ Branch', 'code' => 'HQ-ROS']);
            $shift = $this->createShift($tenantId, $orgId);

            for ($i = 1; $i <= 5; $i++) {
                $emp = $this->createEmployee($tenantId, $orgId, "Emp {$i}", "EMP-ROS-{$i}");
                $this->assignShift($tenantId, $orgId, (int) $emp->id, (int) $shift->id);
            }

            DB::flushQueryLog();
            DB::enableQueryLog();

            $res = $this->getJson("/api/v1/hr/roster?tenant_id={$tenantId}&organization_unit_id={$orgId}&start_date=2026-11-01&end_date=2026-11-10")
                ->assertStatus(200);

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            self::assertCount(5, $res->json('data'));
            // 5 employees * 10 days = 50 cells; batching keeps total queries well under 15 (vs 350+ N+1 queries)
            self::assertLessThan(15, count($queries));
        });
    }

    public function test_kiosk_qr_token_route_requires_manage_attendance_permission(): void
    {
        $route = app('router')->getRoutes()->getByName('api.v1.hr.attendance.capture.kiosk-token');
        self::assertNotNull($route);
        self::assertContains('tenant.permission:hr.attendance.manage', $route->gatherMiddleware());
        self::assertNotContains('tenant.permission:hr.attendance.punch', $route->gatherMiddleware());
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

    private function createShift(int $tenantId, int $orgId): HrShift
    {
        return HrShift::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgId,
            'code' => 'DAY-'.Str::upper(Str::random(4)),
            'name' => 'Standard Day Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'grace_in_minutes' => 15,
            'grace_out_minutes' => 15,
            'auto_deduct_break' => true,
            'auto_deduct_threshold_minutes' => 300,
            'auto_deduct_break_minutes' => 60,
        ]);
    }

    private function assignShift(int $tenantId, int $orgId, int $employeeId, int $shiftId): void
    {
        HrShiftAssignment::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $orgId,
            'employee_id' => $employeeId,
            'shift_id' => $shiftId,
            'start_date' => '2026-01-01',
        ]);
    }

    private function tenant(): int
    {
        $now = now();
        $code = Str::upper(Str::random(6));

        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Capture Test Tenant '.$code,
            'slug' => 'capture-test-tenant-'.strtolower($code),
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Testing Attendance Capture',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActiveTenantSubscriptionFixture::create($tenantId);

        return $tenantId;
    }
}

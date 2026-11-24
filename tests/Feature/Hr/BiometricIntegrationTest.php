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
use LogicException;
use Modules\Hr\Biometric\Services\BiometricDeviceService;
use Modules\Hr\Biometric\Services\BiometricLogIngestionService;
use Modules\Hr\DTOs\BiometricDeviceData;
use Modules\Hr\DTOs\RawBiometricPunchData;
use Modules\Hr\Enums\BiometricProtocol;
use Modules\Hr\Enums\BiometricPunchState;
use Modules\Hr\Enums\BiometricVerifyMethod;
use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Enums\RawPunchSyncStatus;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrBiometricDevice;
use Modules\Hr\Models\HrBiometricDeviceUser;
use Modules\Hr\Models\HrBiometricRawPunch;
use Modules\Hr\Models\HrEmployee;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class BiometricIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_biometric_device_lifecycle_and_optimistic_locking(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $service = app(BiometricDeviceService::class);

            $device = $service->createDevice($tenantId, new BiometricDeviceData(
                deviceCode: 'DEV-ZK-01',
                name: 'Main Gate Terminal',
                protocol: BiometricProtocol::ZkTecoPush,
                serialNumber: 'ZK-SN-12345',
            ));

            self::assertSame('DEV-ZK-01', $device->device_code);
            self::assertSame(1, (int) $device->row_version);

            // Update with correct version
            $updated = $service->updateDevice($device, new BiometricDeviceData(
                deviceCode: 'DEV-ZK-01',
                name: 'Main Gate Terminal Updated',
                protocol: BiometricProtocol::ZkTecoPush,
                serialNumber: 'ZK-SN-12345',
            ), 1);

            self::assertSame('Main Gate Terminal Updated', $updated->name);
            self::assertSame(2, (int) $updated->row_version);

            // Stale update throws LogicException
            $this->expectException(LogicException::class);
            $service->updateDevice($updated, new BiometricDeviceData(
                deviceCode: 'DEV-ZK-01',
                name: 'Stale Attempt',
                protocol: BiometricProtocol::ZkTecoPush,
            ), 1);
        });
    }

    public function test_raw_punch_ingestion_with_deduplication(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'HQ Unit',
                'code' => 'HQ-'.Str::upper(Str::random(4)),
            ]);

            $emp = $this->createEmployee($tenantId, $orgId, 'Alice Johnson', 'EMP-0101');

            $device = HrBiometricDevice::create([
                'tenant_id' => $tenantId,
                'device_code' => 'ZK-IN-01',
                'name' => 'HQ Entrance',
                'protocol' => BiometricProtocol::ZkTecoPush,
                'serial_number' => 'ZK-HQ-01',
                'auto_map_users' => true,
            ]);

            $ingestionService = app(BiometricLogIngestionService::class);

            $punchTime = '2026-11-25 08:30:00';
            $punches = [
                new RawBiometricPunchData(
                    biometricUserId: '101',
                    punchTime: $punchTime,
                    punchState: BiometricPunchState::CheckIn,
                    verifyMethod: BiometricVerifyMethod::Fingerprint,
                ),
            ];

            // 1. First Ingestion
            $stats1 = $ingestionService->ingest($device, $punches);
            self::assertSame(1, $stats1['processed']);
            self::assertSame(0, $stats1['duplicates']);

            $raw = HrBiometricRawPunch::query()->where('tenant_id', $tenantId)->first();
            self::assertNotNull($raw);
            self::assertSame(RawPunchSyncStatus::Processed, $raw->sync_status);
            self::assertSame($emp->id, $raw->employee_id);

            // 2. Second Ingestion with identical stamp (Deduplication assert)
            $stats2 = $ingestionService->ingest($device, $punches);
            self::assertSame(0, $stats2['processed']);
            self::assertSame(1, $stats2['duplicates']);

            // Assert no extra raw punches or attendance records created
            self::assertSame(1, HrBiometricRawPunch::query()->where('tenant_id', $tenantId)->count());
            self::assertSame(1, HrAttendancePunch::query()->where('tenant_id', $tenantId)->count());
        });
    }

    public function test_biometric_user_mapping_and_attendance_generation(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Branch Unit',
                'code' => 'BR-'.Str::upper(Str::random(4)),
            ]);

            $emp = $this->createEmployee($tenantId, $orgId, 'Bob Builder', 'EMP-0202');

            $device = HrBiometricDevice::create([
                'tenant_id' => $tenantId,
                'device_code' => 'BIO-DEV-2',
                'name' => 'Factory Terminal',
                'protocol' => BiometricProtocol::ZkTecoPush,
                'serial_number' => 'FAC-001',
                'auto_map_users' => false, // Require explicit mapping
            ]);

            // Explicitly map device PIN '777' to Bob Builder
            HrBiometricDeviceUser::create([
                'tenant_id' => $tenantId,
                'device_id' => $device->id,
                'biometric_user_id' => '777',
                'employee_id' => $emp->id,
                'is_active' => true,
            ]);

            $ingestionService = app(BiometricLogIngestionService::class);

            // Check-in at 08:00, Check-out at 17:00
            $punches = [
                new RawBiometricPunchData(
                    biometricUserId: '777',
                    punchTime: '2026-11-25 08:00:00',
                    punchState: BiometricPunchState::CheckIn,
                    verifyMethod: BiometricVerifyMethod::Face,
                ),
                new RawBiometricPunchData(
                    biometricUserId: '777',
                    punchTime: '2026-11-25 17:00:00',
                    punchState: BiometricPunchState::CheckOut,
                    verifyMethod: BiometricVerifyMethod::Face,
                ),
            ];

            $stats = $ingestionService->ingest($device, $punches);
            self::assertSame(2, $stats['processed']);

            // Verify attendance punches
            $attPunches = HrAttendancePunch::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $emp->id)
                ->orderBy('punch_time')
                ->get();

            self::assertCount(2, $attPunches);
            self::assertSame(PunchType::In, $attPunches[0]->punch_type);
            self::assertSame(PunchSource::BiometricDevice, $attPunches[0]->punch_source);
            self::assertSame(PunchType::Out, $attPunches[1]->punch_type);

            // Verify daily attendance record
            $record = HrAttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $emp->id)
                ->whereDate('attendance_date', '2026-11-25')
                ->first();

            self::assertNotNull($record);
            self::assertSame(540, (int) $record->total_worked_minutes); // 9 hours = 540 min
        });
    }

    public function test_unmapped_punches_staging_and_subsequent_reprocessing(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Site Unit',
                'code' => 'SITE-'.Str::upper(Str::random(4)),
            ]);

            $device = HrBiometricDevice::create([
                'tenant_id' => $tenantId,
                'device_code' => 'SITE-TERM-1',
                'name' => 'Construction Site Clock',
                'protocol' => BiometricProtocol::ZkTecoPush,
                'auto_map_users' => false,
            ]);

            $ingestionService = app(BiometricLogIngestionService::class);
            $deviceService = app(BiometricDeviceService::class);

            // Ingest punch for unknown PIN 'UNKNOWN-999'
            $punch = new RawBiometricPunchData(
                biometricUserId: 'UNKNOWN-999',
                punchTime: '2026-11-25 07:45:00',
                punchState: BiometricPunchState::CheckIn,
                verifyMethod: BiometricVerifyMethod::Fingerprint,
            );

            $stats = $ingestionService->ingest($device, [$punch]);
            self::assertSame(1, $stats['unmapped']);

            $raw = HrBiometricRawPunch::query()->where('tenant_id', $tenantId)->first();
            self::assertNotNull($raw);
            self::assertSame(RawPunchSyncStatus::UnmappedUser, $raw->sync_status);
            self::assertNull($raw->employee_id);

            // Later: Create employee and map PIN 'UNKNOWN-999'
            $worker = $this->createEmployee($tenantId, $orgId, 'Charlie Crane', 'EMP-CHARLIE');
            $deviceService->mapUser($device, 'UNKNOWN-999', (int) $worker->id);

            // Assert reprocess automatically occurred
            $raw->refresh();
            self::assertSame(RawPunchSyncStatus::Processed, $raw->sync_status);
            self::assertSame($worker->id, $raw->employee_id);
            self::assertNotNull($raw->attendance_punch_id);

            $createdPunch = HrAttendancePunch::find($raw->attendance_punch_id);
            self::assertNotNull($createdPunch);
            self::assertSame($worker->id, $createdPunch->employee_id);
        });
    }

    public function test_public_zkteco_push_receiver_cdata_endpoints(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Push Unit',
                'code' => 'PU-'.Str::upper(Str::random(4)),
            ]);

            $emp = $this->createEmployee($tenantId, $orgId, 'Dan Driver', 'EMP-0505');

            $device = HrBiometricDevice::create([
                'tenant_id' => $tenantId,
                'device_code' => 'ZK-CLOUD-01',
                'name' => 'Warehouse Clock',
                'protocol' => BiometricProtocol::ZkTecoPush,
                'serial_number' => 'ZK-SER-9999',
                'auto_map_users' => true,
            ]);

            // 1. Test GET handshake
            $responseGet = $this->get('/api/v1/hr/biometric/push/zkteco/cdata?SN=ZK-SER-9999&options=all');
            $responseGet->assertStatus(200);
            $responseGet->assertSee('GET OPTION FROM: ZK-SER-9999');

            // 2. Test POST ATTLOG push
            $attlogPayload = "505\t2026-11-25 08:00:00\t0\t1\t0\t0\t0\n";
            $responsePost = $this->call(
                'POST',
                '/api/v1/hr/biometric/push/zkteco/cdata?SN=ZK-SER-9999&table=ATTLOG',
                [],
                [],
                [],
                ['CONTENT_TYPE' => 'text/plain'],
                $attlogPayload,
            );

            $responsePost->assertStatus(200);
            $responsePost->assertSee('OK: 1');

            // Assert punch was ingested
            $raw = HrBiometricRawPunch::query()->where('tenant_id', $tenantId)->first();
            self::assertNotNull($raw);
            self::assertSame('505', $raw->biometric_user_id);
            self::assertSame(RawPunchSyncStatus::Processed, $raw->sync_status);
        });
    }

    public function test_public_generic_webhook_endpoint(): void
    {
        $tenantId = $this->tenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'IoT Unit',
                'code' => 'IOT-'.Str::upper(Str::random(4)),
            ]);

            $emp = $this->createEmployee($tenantId, $orgId, 'Eve Engineer', 'EMP-0606');

            $device = HrBiometricDevice::create([
                'tenant_id' => $tenantId,
                'device_code' => 'IOT-GATEWAY-1',
                'name' => 'Smart Facial Turnstile',
                'protocol' => BiometricProtocol::GenericWebhook,
                'auth_token' => 'secure-token-123',
                'auto_map_users' => true,
            ]);

            $payload = [
                'punches' => [
                    [
                        'user_id' => '606',
                        'punch_time' => '2026-11-25 08:15:00',
                        'state' => 'check_in',
                        'verify_method' => 'face',
                    ],
                ],
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer secure-token-123',
            ])->postJson('/api/v1/hr/biometric/push/webhook/IOT-GATEWAY-1', $payload);

            $response->assertStatus(200);
            $response->assertJsonPath('success', true);
            $response->assertJsonPath('stats.processed', 1);

            $raw = HrBiometricRawPunch::query()->where('tenant_id', $tenantId)->first();
            self::assertNotNull($raw);
            self::assertSame('606', $raw->biometric_user_id);
            self::assertSame(RawPunchSyncStatus::Processed, $raw->sync_status);
        });
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
            'name' => 'Biometric Test Tenant '.$code,
            'slug' => 'biometric-test-tenant-'.strtolower($code),
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Testing Biometric Integration',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ActiveTenantSubscriptionFixture::create($tenantId);

        return $tenantId;
    }
}

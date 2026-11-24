<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Biometric\Services;

use Carbon\CarbonImmutable;
use LogicException;
use Modules\Hr\DTOs\BiometricDeviceData;
use Modules\Hr\DTOs\DeviceHealthResult;
use Modules\Hr\DTOs\DeviceLogSyncResult;
use Modules\Hr\DTOs\PunchData;
use Modules\Hr\DTOs\RawBiometricPunchData;
use Modules\Hr\Enums\BiometricDeviceStatus;
use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Enums\RawPunchSyncStatus;
use Modules\Hr\Models\HrBiometricDevice;
use Modules\Hr\Models\HrBiometricDeviceUser;
use Modules\Hr\Models\HrBiometricRawPunch;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Services\AttendanceCalculationEngine;

final class BiometricDeviceService
{
    public function __construct(
        private readonly BiometricAdapterManager $adapterManager,
        private readonly BiometricLogIngestionService $ingestionService,
        private readonly AttendanceCalculationEngine $attendanceEngine,
    ) {}

    public function createDevice(int $tenantId, BiometricDeviceData $data): HrBiometricDevice
    {
        return HrBiometricDevice::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'device_code' => $data->deviceCode,
            'name' => $data->name,
            'protocol' => $data->protocol,
            'ip_address' => $data->ipAddress,
            'port' => $data->port,
            'serial_number' => $data->serialNumber,
            'comm_key' => $data->commKey,
            'auth_token' => $data->authToken,
            'status' => $data->status,
            'is_enabled' => $data->isEnabled,
            'auto_map_users' => $data->autoMapUsers,
            'settings' => $data->settings ?? [],
            'row_version' => 1,
        ]);
    }

    public function updateDevice(HrBiometricDevice $device, BiometricDeviceData $data, int $expectedVersion): HrBiometricDevice
    {
        if ((int) $device->row_version !== $expectedVersion) {
            throw new LogicException('Concurrency conflict: The device was modified by another session.');
        }

        $device->update([
            'device_code' => $data->deviceCode,
            'name' => $data->name,
            'protocol' => $data->protocol,
            'ip_address' => $data->ipAddress,
            'port' => $data->port,
            'serial_number' => $data->serialNumber,
            'comm_key' => $data->commKey,
            'auth_token' => $data->authToken,
            'status' => $data->status,
            'is_enabled' => $data->isEnabled,
            'auto_map_users' => $data->autoMapUsers,
            'organization_unit_id' => $data->organizationUnitId,
            'settings' => $data->settings ?? $device->settings,
            'row_version' => $expectedVersion + 1,
        ]);

        return $device->fresh();
    }

    public function deleteDevice(HrBiometricDevice $device): void
    {
        $hasPunches = HrBiometricRawPunch::query()
            ->where('tenant_id', $device->tenant_id)
            ->where('device_id', $device->id)
            ->exists();

        if ($hasPunches) {
            // Cannot delete devices with historical logs; disable instead
            $device->update([
                'status' => BiometricDeviceStatus::Inactive,
                'is_enabled' => false,
            ]);

            return;
        }

        $device->deviceUsers()->delete();
        $device->delete();
    }

    public function testConnection(HrBiometricDevice $device): DeviceHealthResult
    {
        $adapter = $this->adapterManager->resolve($device);
        $result = $adapter->ping($device);

        $device->update([
            'last_ping_at' => CarbonImmutable::now(),
            'status' => $result->isReachable ? BiometricDeviceStatus::Active : BiometricDeviceStatus::Offline,
        ]);

        return $result;
    }

    public function syncDeviceLogs(HrBiometricDevice $device): DeviceLogSyncResult
    {
        if (! $device->is_enabled) {
            return DeviceLogSyncResult::failure('Device is disabled.');
        }

        $adapter = $this->adapterManager->resolve($device);
        $syncResult = $adapter->fetchLogs($device);

        if (! $syncResult->isSuccess) {
            $device->update(['status' => BiometricDeviceStatus::Error]);

            return $syncResult;
        }

        if (count($syncResult->punches) > 0) {
            $this->ingestionService->ingest($device, $syncResult->punches);
        }

        return $syncResult;
    }

    public function mapUser(
        HrBiometricDevice $device,
        string $biometricUserId,
        int $employeeId,
        ?string $cardNumber = null,
    ): HrBiometricDeviceUser {
        $tenantId = (int) $device->tenant_id;

        $employee = HrEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $employeeId)
            ->firstOrFail();

        $mapping = HrBiometricDeviceUser::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'device_id' => $device->id,
                'biometric_user_id' => $biometricUserId,
            ],
            [
                'employee_id' => $employee->id,
                'card_number' => $cardNumber,
                'is_active' => true,
                'sync_status' => 'synced',
            ]
        );

        // Auto-reprocess unmapped punches for this user
        $this->reprocessPunchesForUser($device, $biometricUserId, $employee);

        return $mapping;
    }

    public function unmapUser(HrBiometricDeviceUser $mapping): void
    {
        $mapping->delete();
    }

    /**
     * Reprocesses historical punches previously marked as UnmappedUser.
     */
    public function reprocessUnmappedPunches(HrBiometricDevice $device): int
    {
        $unmappedPunches = HrBiometricRawPunch::query()
            ->where('tenant_id', $device->tenant_id)
            ->where('device_id', $device->id)
            ->where('sync_status', RawPunchSyncStatus::UnmappedUser)
            ->get();

        $reprocessedCount = 0;

        foreach ($unmappedPunches as $raw) {
            $punchData = new RawBiometricPunchData(
                biometricUserId: $raw->biometric_user_id,
                punchTime: $raw->punch_time->toDateTimeString(),
                punchState: $raw->punch_state,
                verifyMethod: $raw->verify_method,
                rawPayload: $raw->raw_payload,
            );

            $employee = $this->ingestionService->resolveEmployee($device, $punchData);
            if ($employee !== null) {
                $createdPunch = $this->attendanceEngine->recordPunch(
                    (int) $device->tenant_id,
                    new PunchData(
                        employeeId: (int) $employee->id,
                        punchType: $raw->punch_state->toPunchType() ?? PunchType::In,
                        punchTime: $raw->punch_time->toDateTimeString(),
                        punchSource: PunchSource::BiometricDevice,
                        deviceId: $device->device_code,
                        organizationUnitId: $device->organization_unit_id ?? $employee->organization_unit_id,
                    )
                );

                $raw->update([
                    'employee_id' => $employee->id,
                    'attendance_punch_id' => $createdPunch->id,
                    'sync_status' => RawPunchSyncStatus::Processed,
                    'error_message' => null,
                ]);

                $reprocessedCount++;
            }
        }

        return $reprocessedCount;
    }

    private function reprocessPunchesForUser(HrBiometricDevice $device, string $biometricUserId, HrEmployee $employee): void
    {
        $unmapped = HrBiometricRawPunch::query()
            ->where('tenant_id', $device->tenant_id)
            ->where('device_id', $device->id)
            ->where('biometric_user_id', $biometricUserId)
            ->where('sync_status', RawPunchSyncStatus::UnmappedUser)
            ->get();

        foreach ($unmapped as $raw) {
            $createdPunch = $this->attendanceEngine->recordPunch(
                (int) $device->tenant_id,
                new PunchData(
                    employeeId: (int) $employee->id,
                    punchType: $raw->punch_state->toPunchType() ?? PunchType::In,
                    punchTime: $raw->punch_time->toDateTimeString(),
                    punchSource: PunchSource::BiometricDevice,
                    deviceId: $device->device_code,
                    organizationUnitId: $device->organization_unit_id ?? $employee->organization_unit_id,
                )
            );

            $raw->update([
                'employee_id' => $employee->id,
                'attendance_punch_id' => $createdPunch->id,
                'sync_status' => RawPunchSyncStatus::Processed,
                'error_message' => null,
            ]);
        }
    }
}

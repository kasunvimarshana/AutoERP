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
use Illuminate\Support\Facades\DB;
use Modules\Hr\DTOs\PunchData;
use Modules\Hr\DTOs\RawBiometricPunchData;
use Modules\Hr\Enums\BiometricDeviceStatus;
use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Enums\RawPunchSyncStatus;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Models\HrBiometricDevice;
use Modules\Hr\Models\HrBiometricDeviceUser;
use Modules\Hr\Models\HrBiometricRawPunch;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Services\AttendanceCalculationEngine;

final class BiometricLogIngestionService
{
    public function __construct(
        private readonly AttendanceCalculationEngine $attendanceEngine,
    ) {}

    /**
     * Ingest a batch of raw punches from a biometric device with deduplication and attendance generation.
     *
     * @param  list<RawBiometricPunchData>  $punches
     * @return array{total: int, processed: int, duplicates: int, unmapped: int, failed: int}
     */
    public function ingest(HrBiometricDevice $device, array $punches): array
    {
        $tenantId = (int) $device->tenant_id;
        $deviceId = (int) $device->id;
        $receivedAt = CarbonImmutable::now();

        $stats = [
            'total' => count($punches),
            'processed' => 0,
            'duplicates' => 0,
            'unmapped' => 0,
            'failed' => 0,
        ];

        foreach ($punches as $punchData) {
            $fingerprint = $punchData->generateFingerprint($tenantId, $deviceId);

            // Deduplication Check
            $existing = HrBiometricRawPunch::query()
                ->where('tenant_id', $tenantId)
                ->where('idempotency_hash', $fingerprint)
                ->first();

            if ($existing !== null) {
                $stats['duplicates']++;

                continue;
            }

            DB::beginTransaction();
            try {
                // 1. Stage raw punch record
                $rawRecord = HrBiometricRawPunch::create([
                    'tenant_id' => $tenantId,
                    'device_id' => $deviceId,
                    'biometric_user_id' => $punchData->biometricUserId,
                    'punch_time' => $punchData->punchTime,
                    'received_at' => $receivedAt,
                    'punch_state' => $punchData->punchState,
                    'verify_method' => $punchData->verifyMethod,
                    'idempotency_hash' => $fingerprint,
                    'sync_status' => RawPunchSyncStatus::Pending,
                    'raw_payload' => $punchData->rawPayload,
                ]);

                // 2. Resolve Employee
                $employee = $this->resolveEmployee($device, $punchData);

                if ($employee === null) {
                    $rawRecord->update([
                        'sync_status' => RawPunchSyncStatus::UnmappedUser,
                        'error_message' => "Biometric PIN [{$punchData->biometricUserId}] is not mapped to an active employee.",
                    ]);
                    $stats['unmapped']++;
                    DB::commit();

                    continue;
                }

                // 3. Resolve Direction (In / Out)
                $resolvedPunchType = $this->resolvePunchType($tenantId, (int) $employee->id, $punchData);

                // 4. Construct PunchData and dispatch to AttendanceCalculationEngine
                $enginePunchData = new PunchData(
                    employeeId: (int) $employee->id,
                    punchType: $resolvedPunchType,
                    punchTime: $punchData->punchTime,
                    punchSource: PunchSource::BiometricDevice,
                    captureMethod: CaptureMethod::Biometric,
                    deviceId: $device->device_code,
                    notes: "Biometric stamp from [{$device->name}] via [{$device->protocol->value}]",
                    organizationUnitId: $device->organization_unit_id ?? $employee->organization_unit_id,
                    idempotencyHash: $fingerprint,
                );

                $createdPunch = $this->attendanceEngine->recordPunch($tenantId, $enginePunchData);

                // 5. Update raw record as Processed
                $rawRecord->update([
                    'employee_id' => $employee->id,
                    'attendance_punch_id' => $createdPunch->id,
                    'sync_status' => RawPunchSyncStatus::Processed,
                ]);

                $stats['processed']++;
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $stats['failed']++;

                HrBiometricRawPunch::create([
                    'tenant_id' => $tenantId,
                    'device_id' => $deviceId,
                    'biometric_user_id' => $punchData->biometricUserId,
                    'punch_time' => $punchData->punchTime,
                    'received_at' => $receivedAt,
                    'punch_state' => $punchData->punchState,
                    'verify_method' => $punchData->verifyMethod,
                    'idempotency_hash' => $fingerprint,
                    'sync_status' => RawPunchSyncStatus::Failed,
                    'error_message' => substr($e->getMessage(), 0, 250),
                    'raw_payload' => $punchData->rawPayload,
                ]);
            }
        }

        // Update device statistics
        $device->update([
            'last_sync_at' => $receivedAt,
            'total_punches_synced' => (int) $device->total_punches_synced + $stats['processed'],
            'status' => BiometricDeviceStatus::Active,
        ]);

        return $stats;
    }

    /**
     * Resolve employee mapping for a given biometric punch.
     */
    public function resolveEmployee(HrBiometricDevice $device, RawBiometricPunchData $punchData): ?HrEmployee
    {
        $tenantId = (int) $device->tenant_id;
        $deviceId = (int) $device->id;
        $biometricPin = $punchData->biometricUserId;

        // 1. Direct device mapping
        $mapping = HrBiometricDeviceUser::query()
            ->where('tenant_id', $tenantId)
            ->where('device_id', $deviceId)
            ->where('biometric_user_id', $biometricPin)
            ->where('is_active', true)
            ->first();

        if ($mapping !== null) {
            return HrEmployee::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $mapping->employee_id)
                ->first();
        }

        // 2. Card Number mapping
        if (! empty($punchData->cardNumber)) {
            $cardMapping = HrBiometricDeviceUser::query()
                ->where('tenant_id', $tenantId)
                ->where('card_number', $punchData->cardNumber)
                ->where('is_active', true)
                ->first();

            if ($cardMapping !== null) {
                return HrEmployee::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $cardMapping->employee_id)
                    ->first();
            }
        }

        // 3. Auto-mapping by employee number if enabled on device
        if ($device->auto_map_users) {
            // Check exact employee_number or numeric match (e.g. 101 or EMP-0101)
            $matchedEmp = HrEmployee::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($query) use ($biometricPin): void {
                    $query->where('employee_number', $biometricPin)
                        ->orWhere('employee_number', 'EMP-'.str_pad($biometricPin, 4, '0', STR_PAD_LEFT))
                        ->orWhere('employee_number', 'EMP-'.$biometricPin);
                })
                ->first();

            if ($matchedEmp !== null) {
                // Auto-create device mapping for future efficiency
                HrBiometricDeviceUser::create([
                    'tenant_id' => $tenantId,
                    'device_id' => $deviceId,
                    'biometric_user_id' => $biometricPin,
                    'card_number' => $punchData->cardNumber,
                    'employee_id' => $matchedEmp->id,
                    'is_active' => true,
                    'sync_status' => 'auto_mapped',
                ]);

                return $matchedEmp;
            }
        }

        return null;
    }

    /**
     * Resolve punch type (In/Out/Break) based on explicit state or daily sequence.
     */
    private function resolvePunchType(int $tenantId, int $employeeId, RawBiometricPunchData $punchData): PunchType
    {
        $explicit = $punchData->punchState->toPunchType();
        if ($explicit !== null) {
            return $explicit;
        }

        // Auto mode: check today's existing punches
        $civilDate = CarbonImmutable::parse($punchData->punchTime)->toDateString();

        $lastPunch = HrAttendancePunch::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('attendance_date', $civilDate)
            ->where('is_ignored', false)
            ->orderByDesc('punch_time')
            ->first();

        if ($lastPunch === null || $lastPunch->punch_type === PunchType::Out) {
            return PunchType::In;
        }

        return PunchType::Out;
    }
}

<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Services\Capture\Adapters;

use InvalidArgumentException;
use Modules\Hr\Biometric\Services\BiometricLogIngestionService;
use Modules\Hr\Contracts\AttendanceCaptureAdapterInterface;
use Modules\Hr\DTOs\RawBiometricPunchData;
use Modules\Hr\Enums\BiometricPunchState;
use Modules\Hr\Enums\BiometricVerifyMethod;
use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Models\HrBiometricDevice;

final class BiometricCaptureAdapter implements AttendanceCaptureAdapterInterface
{
    public function __construct(
        private readonly BiometricLogIngestionService $ingestionService,
    ) {}

    public function method(): CaptureMethod
    {
        return CaptureMethod::Biometric;
    }

    public function supports(CaptureMethod $method): bool
    {
        return $method === CaptureMethod::Biometric;
    }

    public function capture(int $tenantId, array $payload): HrAttendancePunch
    {
        $deviceId = (int) ($payload['biometric_device_id'] ?? 0);
        $deviceCode = (string) ($payload['device_code'] ?? '');
        $biometricUserId = trim((string) ($payload['biometric_user_id'] ?? ''));

        if ($biometricUserId === '' || ($deviceId <= 0 && $deviceCode === '')) {
            throw new InvalidArgumentException('biometric_user_id and biometric_device_id (or device_code) are required.');
        }

        $device = HrBiometricDevice::query()
            ->where('tenant_id', $tenantId)
            ->when($deviceId > 0, fn ($q) => $q->where('id', $deviceId), fn ($q) => $q->where('device_code', $deviceCode))
            ->firstOrFail();

        $punchTime = (string) ($payload['punch_time'] ?? now()->toDateTimeString());
        $punchState = ($payload['punch_state'] ?? null) instanceof BiometricPunchState
            ? $payload['punch_state']
            : (BiometricPunchState::tryFrom((int) ($payload['punch_state'] ?? 0)) ?? BiometricPunchState::CheckIn);
        $verifyMethod = ($payload['verify_method'] ?? null) instanceof BiometricVerifyMethod
            ? $payload['verify_method']
            : (BiometricVerifyMethod::tryFrom((int) ($payload['verify_method'] ?? 0)) ?? BiometricVerifyMethod::Fingerprint);

        $rawPunch = new RawBiometricPunchData(
            biometricUserId: $biometricUserId,
            punchTime: $punchTime,
            punchState: $punchState,
            verifyMethod: $verifyMethod,
            rawPayload: is_array($payload['raw_payload'] ?? null) ? $payload['raw_payload'] : $payload,
        );

        $stats = $this->ingestionService->ingest($device, [$rawPunch]);
        if ($stats['unmapped'] > 0) {
            throw new InvalidArgumentException("Biometric user [{$biometricUserId}] is not mapped to an employee.");
        }
        if ($stats['failed'] > 0) {
            throw new InvalidArgumentException('Failed to process biometric punch.');
        }

        $fingerprint = $rawPunch->generateFingerprint($tenantId, (int) $device->id);

        return HrAttendancePunch::query()
            ->where('tenant_id', $tenantId)
            ->where('idempotency_hash', $fingerprint)
            ->firstOrFail();
    }
}

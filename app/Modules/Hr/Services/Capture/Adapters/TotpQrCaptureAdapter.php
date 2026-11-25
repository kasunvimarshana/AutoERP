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

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\Hr\Contracts\AttendanceCaptureAdapterInterface;
use Modules\Hr\DTOs\PunchData;
use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Models\HrAttendanceKiosk;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Services\AttendanceCalculationEngine;
use Modules\Hr\Services\Capture\DynamicQrSecurityService;
use Modules\Hr\Services\Capture\GeofenceVerificationService;

final class TotpQrCaptureAdapter implements AttendanceCaptureAdapterInterface
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
        private readonly GeofenceVerificationService $geofenceService,
        private readonly DynamicQrSecurityService $qrSecurityService,
    ) {}

    public function method(): CaptureMethod
    {
        return CaptureMethod::TotpQr;
    }

    public function supports(CaptureMethod $method): bool
    {
        return $method === CaptureMethod::TotpQr;
    }

    public function capture(int $tenantId, array $payload): HrAttendancePunch
    {
        $employeeId = (int) ($payload['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            throw new InvalidArgumentException('employee_id is required for QR scan punch.');
        }

        $qrToken = (string) ($payload['qr_token'] ?? '');
        if ($qrToken === '') {
            throw new InvalidArgumentException('qr_token is required for QR scan punch.');
        }

        $punchType = ($payload['punch_type'] ?? null) instanceof PunchType
            ? $payload['punch_type']
            : PunchType::tryFrom((string) ($payload['punch_type'] ?? ''));
        if ($punchType === null) {
            throw new InvalidArgumentException('Valid punch_type is required for QR scan punch.');
        }

        $decoded = base64_decode($qrToken, true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid QR token encoding.');
        }

        $parts = explode(':', $decoded);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Malformed QR token payload.');
        }

        $kioskId = (int) $parts[0];
        $kiosk = HrAttendanceKiosk::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $kioskId)
            ->where('is_active', true)
            ->first();

        if ($kiosk === null) {
            throw new InvalidArgumentException('Kiosk terminal not found or inactive.');
        }

        if (! $this->qrSecurityService->verifyToken($kiosk, $qrToken)) {
            throw new InvalidArgumentException('Dynamic QR code has expired or signature is invalid. Please scan the current code.');
        }

        $kiosk->update(['last_heartbeat_at' => CarbonImmutable::now()]);

        $latitude = isset($payload['latitude']) ? (float) $payload['latitude'] : null;
        $longitude = isset($payload['longitude']) ? (float) $payload['longitude'] : null;

        if ($kiosk->geofence_id !== null && $latitude !== null && $longitude !== null) {
            $geofence = $kiosk->geofence;
            if ($geofence !== null && ! $this->geofenceService->isWithinGeofence($geofence, $latitude, $longitude)) {
                throw new InvalidArgumentException("Scan location ({$latitude}, {$longitude}) is outside kiosk geofence.");
            }
        }

        $now = isset($payload['punch_time'])
            ? CarbonImmutable::parse((string) $payload['punch_time'])
            : CarbonImmutable::now();

        $idempotencyHash = isset($payload['idempotency_hash']) && is_string($payload['idempotency_hash']) && $payload['idempotency_hash'] !== ''
            ? $payload['idempotency_hash']
            : hash('sha256', "kiosk_qr:{$tenantId}:{$employeeId}:{$punchType->value}:{$now->format('Y-m-d H:i')}");

        return $this->calculationEngine->recordPunch($tenantId, new PunchData(
            employeeId: $employeeId,
            punchType: $punchType,
            punchTime: $now->toDateTimeString(),
            punchSource: PunchSource::Mobile,
            captureMethod: CaptureMethod::TotpQr,
            deviceId: (string) $kiosk->kiosk_code,
            latitude: $latitude !== null ? (string) $latitude : null,
            longitude: $longitude !== null ? (string) $longitude : null,
            deviceFingerprint: isset($payload['device_uuid']) ? (string) $payload['device_uuid'] : null,
            geofenceId: $kiosk->geofence_id,
            notes: "Scanned dynamic QR at kiosk [{$kiosk->name}]",
            organizationUnitId: isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            idempotencyHash: $idempotencyHash,
        ));
    }
}

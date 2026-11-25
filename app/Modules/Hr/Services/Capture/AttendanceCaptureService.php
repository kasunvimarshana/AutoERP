<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Services\Capture;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\Hr\DTOs\PunchData;
use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Enums\CredentialStatus;
use Modules\Hr\Enums\CredentialType;
use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Models\HrAttendanceGeofence;
use Modules\Hr\Models\HrAttendanceKiosk;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Models\HrEmployeeAttendanceCredential;
use Modules\Hr\Services\AttendanceCalculationEngine;

final class AttendanceCaptureService
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
        private readonly GeofenceVerificationService $geofenceService,
        private readonly DynamicQrSecurityService $qrSecurityService,
    ) {}

    /**
     * Record web self-service punch.
     */
    public function recordWebPunch(
        int $tenantId,
        int $employeeId,
        PunchType $punchType,
        ?string $ipAddress = null,
        ?string $notes = null,
    ): HrAttendancePunch {
        $now = CarbonImmutable::now();
        $idempotencyHash = hash('sha256', "web:{$tenantId}:{$employeeId}:{$punchType->value}:{$now->format('Y-m-d H:i')}");

        return $this->calculationEngine->recordPunch($tenantId, new PunchData(
            employeeId: $employeeId,
            punchType: $punchType,
            punchTime: $now->toDateTimeString(),
            punchSource: PunchSource::Web,
            captureMethod: CaptureMethod::WebPortal,
            ipAddress: $ipAddress,
            notes: $notes,
            idempotencyHash: $idempotencyHash,
        ));
    }

    /**
     * Record mobile punch with GPS geofencing and optional device UUID / selfie check.
     */
    public function recordMobilePunch(
        int $tenantId,
        int $employeeId,
        PunchType $punchType,
        float $latitude,
        float $longitude,
        ?int $accuracyMeters = null,
        ?string $deviceUuid = null,
        ?string $photoPath = null,
        ?string $notes = null,
        ?string $ipAddress = null,
        ?int $geofenceId = null,
    ): HrAttendancePunch {
        $geofence = null;
        if ($geofenceId !== null) {
            $geofence = HrAttendanceGeofence::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $geofenceId)
                ->where('is_active', true)
                ->first();
        }

        // If no explicit geofence was given, find any active geofence covering these coordinates
        if ($geofence === null) {
            $activeGeofences = HrAttendanceGeofence::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get();

            foreach ($activeGeofences as $geo) {
                if ($this->geofenceService->isWithinGeofence($geo, $latitude, $longitude, $accuracyMeters ?? 0)) {
                    $geofence = $geo;
                    break;
                }
            }
        }

        if ($geofence !== null) {
            if (! $this->geofenceService->isWithinGeofence($geofence, $latitude, $longitude, $accuracyMeters ?? 0)) {
                throw new InvalidArgumentException("Coordinates ({$latitude}, {$longitude}) violate geofence [{$geofence->name}].");
            }
            if (! $this->geofenceService->isIpWhitelisted($geofence, $ipAddress)) {
                throw new InvalidArgumentException("Client IP [{$ipAddress}] is not authorized for geofence [{$geofence->name}].");
            }
        }

        $now = CarbonImmutable::now();
        $idempotencyHash = hash('sha256', "mobile:{$tenantId}:{$employeeId}:{$punchType->value}:{$now->format('Y-m-d H:i')}");

        return $this->calculationEngine->recordPunch($tenantId, new PunchData(
            employeeId: $employeeId,
            punchType: $punchType,
            punchTime: $now->toDateTimeString(),
            punchSource: PunchSource::Mobile,
            captureMethod: CaptureMethod::MobileGps,
            ipAddress: $ipAddress,
            latitude: (string) $latitude,
            longitude: (string) $longitude,
            accuracyMeters: $accuracyMeters,
            deviceFingerprint: $deviceUuid,
            photoPath: $photoPath,
            geofenceId: $geofence?->getKey(),
            notes: $notes,
            idempotencyHash: $idempotencyHash,
        ));
    }

    /**
     * Record RFID smart card tap.
     */
    public function recordRfidPunch(
        int $tenantId,
        string $cardUid,
        PunchType $punchType,
        ?string $deviceId = null,
        ?string $ipAddress = null,
    ): HrAttendancePunch {
        $credential = $this->resolveActiveCredential($tenantId, CredentialType::RfidCard, $cardUid);

        $now = CarbonImmutable::now();
        $idempotencyHash = hash('sha256', "rfid:{$tenantId}:{$credential->employee_id}:{$punchType->value}:{$now->format('Y-m-d H:i')}");

        return $this->calculationEngine->recordPunch($tenantId, new PunchData(
            employeeId: (int) $credential->employee_id,
            punchType: $punchType,
            punchTime: $now->toDateTimeString(),
            punchSource: PunchSource::BiometricDevice,
            captureMethod: CaptureMethod::RfidCard,
            deviceId: $deviceId,
            ipAddress: $ipAddress,
            credentialId: (int) $credential->id,
            idempotencyHash: $idempotencyHash,
        ));
    }

    /**
     * Record Barcode scan.
     */
    public function recordBarcodePunch(
        int $tenantId,
        string $barcode,
        PunchType $punchType,
        ?string $deviceId = null,
    ): HrAttendancePunch {
        $credential = $this->resolveActiveCredential($tenantId, CredentialType::Barcode, $barcode);

        $now = CarbonImmutable::now();
        $idempotencyHash = hash('sha256', "barcode:{$tenantId}:{$credential->employee_id}:{$punchType->value}:{$now->format('Y-m-d H:i')}");

        return $this->calculationEngine->recordPunch($tenantId, new PunchData(
            employeeId: (int) $credential->employee_id,
            punchType: $punchType,
            punchTime: $now->toDateTimeString(),
            punchSource: PunchSource::BiometricDevice,
            captureMethod: CaptureMethod::Barcode,
            deviceId: $deviceId,
            credentialId: (int) $credential->id,
            idempotencyHash: $idempotencyHash,
        ));
    }

    /**
     * Record punch by scanning dynamic rotating QR token on stationary kiosk terminal.
     */
    public function recordKioskQrPunch(
        int $tenantId,
        int $employeeId,
        string $qrToken,
        PunchType $punchType,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $deviceUuid = null,
    ): HrAttendancePunch {
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

        // Update kiosk heartbeat
        $kiosk->update(['last_heartbeat_at' => CarbonImmutable::now()]);

        // If kiosk has a bound geofence and coordinates are passed, verify proximity
        if ($kiosk->geofence_id !== null && $latitude !== null && $longitude !== null) {
            $geofence = $kiosk->geofence;
            if ($geofence !== null && ! $this->geofenceService->isWithinGeofence($geofence, $latitude, $longitude)) {
                throw new InvalidArgumentException("Scan location ({$latitude}, {$longitude}) is outside kiosk geofence.");
            }
        }

        $now = CarbonImmutable::now();
        $idempotencyHash = hash('sha256', "kiosk_qr:{$tenantId}:{$employeeId}:{$punchType->value}:{$now->format('Y-m-d H:i')}");

        return $this->calculationEngine->recordPunch($tenantId, new PunchData(
            employeeId: $employeeId,
            punchType: $punchType,
            punchTime: $now->toDateTimeString(),
            punchSource: PunchSource::Mobile,
            captureMethod: CaptureMethod::TotpQr,
            deviceId: (string) $kiosk->kiosk_code,
            latitude: $latitude !== null ? (string) $latitude : null,
            longitude: $longitude !== null ? (string) $longitude : null,
            deviceFingerprint: $deviceUuid,
            geofenceId: $kiosk->geofence_id,
            notes: "Scanned dynamic QR at kiosk [{$kiosk->name}]",
            idempotencyHash: $idempotencyHash,
        ));
    }

    /**
     * Resolve active employee credential.
     */
    private function resolveActiveCredential(int $tenantId, CredentialType $type, string $value): HrEmployeeAttendanceCredential
    {
        $credential = HrEmployeeAttendanceCredential::query()
            ->where('tenant_id', $tenantId)
            ->where('credential_type', $type)
            ->where('credential_value', trim($value))
            ->first();

        if ($credential === null) {
            throw new InvalidArgumentException("Unrecognized {$type->value} credential [{$value}].");
        }

        if ($credential->status !== CredentialStatus::Active) {
            throw new InvalidArgumentException("Credential [{$value}] is currently {$credential->status->value}.");
        }

        if ($credential->expires_at !== null && CarbonImmutable::parse($credential->expires_at)->isPast()) {
            throw new InvalidArgumentException("Credential [{$value}] expired on {$credential->expires_at}.");
        }

        return $credential;
    }
}

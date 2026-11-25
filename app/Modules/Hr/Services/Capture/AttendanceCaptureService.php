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

use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Models\HrAttendancePunch;

final class AttendanceCaptureService
{
    public function __construct(
        private readonly AttendanceCaptureAdapterManager $adapterManager,
    ) {}

    /**
     * Unified adapter-based capture entry point for all capture channels.
     *
     * @param  array<string, mixed>  $payload
     */
    public function capture(int $tenantId, CaptureMethod $method, array $payload): HrAttendancePunch
    {
        return $this->adapterManager->capture($tenantId, $method, $payload);
    }

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
        return $this->adapterManager->capture($tenantId, CaptureMethod::WebPortal, [
            'employee_id' => $employeeId,
            'punch_type' => $punchType,
            'ip_address' => $ipAddress,
            'notes' => $notes,
        ]);
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
        return $this->adapterManager->capture($tenantId, CaptureMethod::MobileGps, [
            'employee_id' => $employeeId,
            'punch_type' => $punchType,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meters' => $accuracyMeters,
            'device_uuid' => $deviceUuid,
            'photo_path' => $photoPath,
            'notes' => $notes,
            'ip_address' => $ipAddress,
            'geofence_id' => $geofenceId,
        ]);
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
        return $this->adapterManager->capture($tenantId, CaptureMethod::RfidCard, [
            'card_uid' => $cardUid,
            'punch_type' => $punchType,
            'device_id' => $deviceId,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Record NFC tag tap.
     */
    public function recordNfcPunch(
        int $tenantId,
        string $tagUid,
        PunchType $punchType,
        ?string $deviceId = null,
        ?string $deviceUuid = null,
        ?string $ipAddress = null,
    ): HrAttendancePunch {
        return $this->adapterManager->capture($tenantId, CaptureMethod::NfcTag, [
            'tag_uid' => $tagUid,
            'punch_type' => $punchType,
            'device_id' => $deviceId,
            'device_uuid' => $deviceUuid,
            'ip_address' => $ipAddress,
        ]);
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
        return $this->adapterManager->capture($tenantId, CaptureMethod::Barcode, [
            'barcode' => $barcode,
            'punch_type' => $punchType,
            'device_id' => $deviceId,
        ]);
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
        return $this->adapterManager->capture($tenantId, CaptureMethod::TotpQr, [
            'employee_id' => $employeeId,
            'qr_token' => $qrToken,
            'punch_type' => $punchType,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'device_uuid' => $deviceUuid,
        ]);
    }

    /**
     * Record manual admin punch entry.
     */
    public function recordManualPunch(
        int $tenantId,
        int $employeeId,
        PunchType $punchType,
        ?string $punchTime = null,
        ?string $ipAddress = null,
        ?string $notes = null,
    ): HrAttendancePunch {
        return $this->adapterManager->capture($tenantId, CaptureMethod::ManualAdmin, [
            'employee_id' => $employeeId,
            'punch_type' => $punchType,
            'punch_time' => $punchTime,
            'ip_address' => $ipAddress,
            'notes' => $notes,
        ]);
    }
}

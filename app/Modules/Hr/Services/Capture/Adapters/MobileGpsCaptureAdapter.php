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
use Modules\Hr\Models\HrAttendanceGeofence;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Services\AttendanceCalculationEngine;
use Modules\Hr\Services\Capture\GeofenceVerificationService;

final class MobileGpsCaptureAdapter implements AttendanceCaptureAdapterInterface
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
        private readonly GeofenceVerificationService $geofenceService,
    ) {}

    public function method(): CaptureMethod
    {
        return CaptureMethod::MobileGps;
    }

    public function supports(CaptureMethod $method): bool
    {
        return $method === CaptureMethod::MobileGps;
    }

    public function capture(int $tenantId, array $payload): HrAttendancePunch
    {
        $employeeId = (int) ($payload['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            throw new InvalidArgumentException('employee_id is required for mobile GPS punch.');
        }

        $punchType = ($payload['punch_type'] ?? null) instanceof PunchType
            ? $payload['punch_type']
            : PunchType::tryFrom((string) ($payload['punch_type'] ?? ''));
        if ($punchType === null) {
            throw new InvalidArgumentException('Valid punch_type is required for mobile GPS punch.');
        }

        if (! isset($payload['latitude'], $payload['longitude'])) {
            throw new InvalidArgumentException('latitude and longitude are required for mobile GPS punch.');
        }

        $latitude = (float) $payload['latitude'];
        $longitude = (float) $payload['longitude'];
        $accuracyMeters = isset($payload['accuracy_meters']) ? (int) $payload['accuracy_meters'] : null;
        $ipAddress = isset($payload['ip_address']) ? (string) $payload['ip_address'] : null;
        $geofenceId = isset($payload['geofence_id']) ? (int) $payload['geofence_id'] : null;

        $geofence = null;
        if ($geofenceId !== null) {
            $geofence = HrAttendanceGeofence::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $geofenceId)
                ->where('is_active', true)
                ->first();
        }

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

        $now = isset($payload['punch_time'])
            ? CarbonImmutable::parse((string) $payload['punch_time'])
            : CarbonImmutable::now();

        $idempotencyHash = isset($payload['idempotency_hash']) && is_string($payload['idempotency_hash']) && $payload['idempotency_hash'] !== ''
            ? $payload['idempotency_hash']
            : hash('sha256', "mobile:{$tenantId}:{$employeeId}:{$punchType->value}:{$now->format('Y-m-d H:i')}");

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
            deviceFingerprint: isset($payload['device_uuid']) ? (string) $payload['device_uuid'] : null,
            photoPath: isset($payload['photo_path']) ? (string) $payload['photo_path'] : null,
            geofenceId: $geofence?->getKey(),
            notes: isset($payload['notes']) ? (string) $payload['notes'] : null,
            organizationUnitId: isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            idempotencyHash: $idempotencyHash,
        ));
    }
}

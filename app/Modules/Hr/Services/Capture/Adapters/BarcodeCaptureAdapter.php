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
use Modules\Hr\Enums\CredentialStatus;
use Modules\Hr\Enums\CredentialType;
use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Models\HrEmployeeAttendanceCredential;
use Modules\Hr\Services\AttendanceCalculationEngine;

final class BarcodeCaptureAdapter implements AttendanceCaptureAdapterInterface
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
    ) {}

    public function method(): CaptureMethod
    {
        return CaptureMethod::Barcode;
    }

    public function supports(CaptureMethod $method): bool
    {
        return $method === CaptureMethod::Barcode;
    }

    public function capture(int $tenantId, array $payload): HrAttendancePunch
    {
        $barcode = trim((string) ($payload['barcode'] ?? $payload['credential_value'] ?? ''));
        if ($barcode === '') {
            throw new InvalidArgumentException('barcode is required for barcode punch.');
        }

        $punchType = ($payload['punch_type'] ?? null) instanceof PunchType
            ? $payload['punch_type']
            : PunchType::tryFrom((string) ($payload['punch_type'] ?? ''));
        if ($punchType === null) {
            throw new InvalidArgumentException('Valid punch_type is required for barcode punch.');
        }

        $credential = HrEmployeeAttendanceCredential::query()
            ->where('tenant_id', $tenantId)
            ->where('credential_type', CredentialType::Barcode)
            ->where('credential_value', $barcode)
            ->first();

        if ($credential === null) {
            throw new InvalidArgumentException("Unrecognized barcode credential [{$barcode}].");
        }

        if ($credential->status !== CredentialStatus::Active) {
            throw new InvalidArgumentException("Credential [{$barcode}] is currently {$credential->status->value}.");
        }

        if ($credential->expires_at !== null && $credential->expires_at->isPast()) {
            throw new InvalidArgumentException("Credential [{$barcode}] expired on {$credential->expires_at}.");
        }

        $now = isset($payload['punch_time'])
            ? CarbonImmutable::parse((string) $payload['punch_time'])
            : CarbonImmutable::now();

        $idempotencyHash = isset($payload['idempotency_hash']) && is_string($payload['idempotency_hash']) && $payload['idempotency_hash'] !== ''
            ? $payload['idempotency_hash']
            : hash('sha256', "barcode:{$tenantId}:{$credential->employee_id}:{$punchType->value}:{$now->format('Y-m-d H:i')}");

        return $this->calculationEngine->recordPunch($tenantId, new PunchData(
            employeeId: (int) $credential->employee_id,
            punchType: $punchType,
            punchTime: $now->toDateTimeString(),
            punchSource: PunchSource::BiometricDevice,
            captureMethod: CaptureMethod::Barcode,
            deviceId: isset($payload['device_id']) ? (string) $payload['device_id'] : null,
            ipAddress: isset($payload['ip_address']) ? (string) $payload['ip_address'] : null,
            credentialId: (int) $credential->id,
            organizationUnitId: isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            idempotencyHash: $idempotencyHash,
        ));
    }
}

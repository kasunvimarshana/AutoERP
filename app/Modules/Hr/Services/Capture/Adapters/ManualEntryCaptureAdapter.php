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
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Services\AttendanceCalculationEngine;

final class ManualEntryCaptureAdapter implements AttendanceCaptureAdapterInterface
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
    ) {}

    public function method(): CaptureMethod
    {
        return CaptureMethod::ManualAdmin;
    }

    public function supports(CaptureMethod $method): bool
    {
        return $method === CaptureMethod::ManualAdmin;
    }

    public function capture(int $tenantId, array $payload): HrAttendancePunch
    {
        $employeeId = (int) ($payload['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            throw new InvalidArgumentException('employee_id is required for manual attendance entry.');
        }

        $punchType = ($payload['punch_type'] ?? null) instanceof PunchType
            ? $payload['punch_type']
            : PunchType::tryFrom((string) ($payload['punch_type'] ?? ''));
        if ($punchType === null) {
            throw new InvalidArgumentException('Valid punch_type is required for manual attendance entry.');
        }

        $punchTime = isset($payload['punch_time'])
            ? CarbonImmutable::parse((string) $payload['punch_time'])
            : CarbonImmutable::now();

        $idempotencyHash = isset($payload['idempotency_hash']) && is_string($payload['idempotency_hash']) && $payload['idempotency_hash'] !== ''
            ? $payload['idempotency_hash']
            : hash('sha256', "manual:{$tenantId}:{$employeeId}:{$punchType->value}:{$punchTime->toDateTimeString()}");

        return $this->calculationEngine->recordPunch($tenantId, new PunchData(
            employeeId: $employeeId,
            punchType: $punchType,
            punchTime: $punchTime->toDateTimeString(),
            punchSource: PunchSource::ManualEntry,
            captureMethod: CaptureMethod::ManualAdmin,
            deviceId: isset($payload['device_id']) ? (string) $payload['device_id'] : null,
            ipAddress: isset($payload['ip_address']) ? (string) $payload['ip_address'] : null,
            notes: isset($payload['notes']) ? (string) $payload['notes'] : 'Manual admin entry',
            organizationUnitId: isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            idempotencyHash: $idempotencyHash,
        ));
    }
}

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

final class FileUploadCaptureAdapter implements AttendanceCaptureAdapterInterface
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
    ) {}

    public function method(): CaptureMethod
    {
        return CaptureMethod::FileUpload;
    }

    public function supports(CaptureMethod $method): bool
    {
        return $method === CaptureMethod::FileUpload;
    }

    public function capture(int $tenantId, array $payload): HrAttendancePunch
    {
        $employeeId = (int) ($payload['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            throw new InvalidArgumentException('employee_id is required for file upload punch.');
        }

        $punchType = ($payload['punch_type'] ?? null) instanceof PunchType
            ? $payload['punch_type']
            : PunchType::tryFrom((string) ($payload['punch_type'] ?? ''));
        if ($punchType === null) {
            throw new InvalidArgumentException('Valid punch_type is required for file upload punch.');
        }

        $rawPunchTime = trim((string) ($payload['punch_time'] ?? ''));
        if ($rawPunchTime === '') {
            throw new InvalidArgumentException('punch_time is required for file upload punch.');
        }

        $punchTime = CarbonImmutable::parse($rawPunchTime);
        $idempotencyHash = isset($payload['idempotency_hash']) && is_string($payload['idempotency_hash']) && $payload['idempotency_hash'] !== ''
            ? $payload['idempotency_hash']
            : hash('sha256', "import:{$tenantId}:{$employeeId}:{$punchType->value}:{$punchTime->toDateTimeString()}");

        return $this->calculationEngine->recordPunch($tenantId, new PunchData(
            employeeId: $employeeId,
            punchType: $punchType,
            punchTime: $punchTime->toDateTimeString(),
            punchSource: PunchSource::ManualEntry,
            captureMethod: CaptureMethod::FileUpload,
            notes: isset($payload['notes']) ? (string) $payload['notes'] : null,
            organizationUnitId: isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            idempotencyHash: $idempotencyHash,
        ));
    }
}

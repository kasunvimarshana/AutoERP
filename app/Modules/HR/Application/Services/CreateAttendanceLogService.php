<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\CreateAttendanceLogServiceInterface;
use Modules\HR\Application\DTOs\AttendanceLogData;
use Modules\HR\Domain\Entities\AttendanceLog;
use Modules\HR\Domain\Events\AttendanceLogCreated;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceLogRepositoryInterface;

class CreateAttendanceLogService extends BaseService implements CreateAttendanceLogServiceInterface
{
    public function __construct(
        private readonly AttendanceLogRepositoryInterface $logRepository,
    ) {
        parent::__construct($this->logRepository);
    }

    protected function handle(array $data): AttendanceLog
    {
        $dto = AttendanceLogData::fromArray($data);

        $now = new \DateTimeImmutable;
        $log = new AttendanceLog(
            tenantId: $dto->tenantId,
            employeeId: $dto->employeeId,
            biometricDeviceId: $dto->biometricDeviceId,
            punchTime: new \DateTimeImmutable($dto->punchTime),
            punchType: $dto->punchType,
            source: $dto->source,
            rawData: $dto->rawData,
            processedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );

        $saved = $this->logRepository->save($log);

        $this->addEvent(new AttendanceLogCreated($saved, $dto->tenantId));

        return $saved;
    }
}

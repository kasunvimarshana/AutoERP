<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\UpdateAttendanceRecordServiceInterface;
use Modules\HR\Application\DTOs\AttendanceRecordData;
use Modules\HR\Domain\Entities\AttendanceRecord;
use Modules\HR\Domain\Exceptions\AttendanceRecordNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\ValueObjects\AttendanceStatus;

class UpdateAttendanceRecordService extends BaseService implements UpdateAttendanceRecordServiceInterface
{
    public function __construct(
        private readonly AttendanceRecordRepositoryInterface $recordRepository,
    ) {
        parent::__construct($this->recordRepository);
    }

    protected function handle(array $data): AttendanceRecord
    {
        $id = (int) ($data['id'] ?? 0);
        $record = $this->recordRepository->find($id);

        if ($record === null) {
            throw new AttendanceRecordNotFoundException($id);
        }

        $dto = AttendanceRecordData::fromArray($data);

        if ($record->getTenantId() !== $dto->tenantId) {
            throw new AttendanceRecordNotFoundException($id);
        }

        $checkIn = $dto->checkIn !== null ? new \DateTimeImmutable($dto->checkIn) : null;
        $checkOut = $dto->checkOut !== null ? new \DateTimeImmutable($dto->checkOut) : null;

        $workedMinutes = 0;
        if ($checkIn !== null && $checkOut !== null) {
            $diff = $checkOut->getTimestamp() - $checkIn->getTimestamp();
            $workedMinutes = max(0, (int) ($diff / 60) - $dto->breakDuration);
        }

        $updated = new AttendanceRecord(
            tenantId: $record->getTenantId(),
            employeeId: $record->getEmployeeId(),
            attendanceDate: $record->getAttendanceDate(),
            checkIn: $checkIn,
            checkOut: $checkOut,
            breakDuration: $dto->breakDuration,
            workedMinutes: $workedMinutes,
            overtimeMinutes: $record->getOvertimeMinutes(),
            status: AttendanceStatus::from($dto->status),
            shiftId: $dto->shiftId,
            remarks: $dto->remarks,
            metadata: $dto->metadata,
            createdAt: $record->getCreatedAt(),
            updatedAt: new \DateTimeImmutable,
            id: $record->getId(),
        );

        return $this->recordRepository->save($updated);
    }
}

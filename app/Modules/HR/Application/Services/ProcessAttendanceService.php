<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\ProcessAttendanceServiceInterface;
use Modules\HR\Application\DTOs\ProcessAttendanceData;
use Modules\HR\Domain\Entities\AttendanceRecord;
use Modules\HR\Domain\Events\AttendanceRecordProcessed;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceLogRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\ShiftAssignmentRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\ShiftRepositoryInterface;
use Modules\HR\Domain\ValueObjects\AttendanceStatus;

class ProcessAttendanceService extends BaseService implements ProcessAttendanceServiceInterface
{
    public function __construct(
        private readonly AttendanceRecordRepositoryInterface $recordRepository,
        private readonly AttendanceLogRepositoryInterface $logRepository,
        private readonly ShiftAssignmentRepositoryInterface $shiftAssignmentRepository,
        private readonly ShiftRepositoryInterface $shiftRepository,
    ) {
        parent::__construct($this->recordRepository);
    }

    protected function handle(array $data): AttendanceRecord
    {
        $dto = ProcessAttendanceData::fromArray($data);
        $logs = $this->logRepository->findByEmployeeAndDate(
            $dto->tenantId,
            $dto->employeeId,
            $dto->attendanceDate,
        );

        usort($logs, fn ($a, $b) => $a->getPunchTime() <=> $b->getPunchTime());

        $checkIn = null;
        $checkOut = null;
        $breakMins = 0;

        $breakStart = null;
        foreach ($logs as $log) {
            switch ($log->getPunchType()) {
                case 'check_in':
                    if ($checkIn === null) {
                        $checkIn = $log->getPunchTime();
                    }
                    break;
                case 'check_out':
                    $checkOut = $log->getPunchTime();
                    break;
                case 'break_start':
                    $breakStart = $log->getPunchTime();
                    break;
                case 'break_end':
                    if ($breakStart !== null) {
                        $diff = $log->getPunchTime()->getTimestamp() - $breakStart->getTimestamp();
                        $breakMins += (int) ($diff / 60);
                        $breakStart = null;
                    }
                    break;
            }
        }

        $shiftAssignment = $this->shiftAssignmentRepository->findCurrentForEmployee(
            $dto->tenantId,
            $dto->employeeId,
            $dto->attendanceDate,
        );

        $shiftId = $shiftAssignment?->getShiftId();
        $shift = $shiftId !== null ? $this->shiftRepository->find($shiftId) : null;
        $workedMinutes = 0;
        $overtimeMinutes = 0;

        if ($checkIn !== null && $checkOut !== null) {
            $diff = $checkOut->getTimestamp() - $checkIn->getTimestamp();
            $workedMinutes = max(0, (int) ($diff / 60) - $breakMins);

            if ($shift !== null) {
                $overtime = $workedMinutes - $shift->getOvertimeThreshold();
                $overtimeMinutes = max(0, $overtime);
            }
        }

        $status = $this->determineStatus($checkIn, $shift, $breakMins);

        $existing = $this->recordRepository->findByEmployeeAndDate(
            $dto->tenantId,
            $dto->employeeId,
            $dto->attendanceDate,
        );

        $now = new \DateTimeImmutable;

        if ($existing !== null) {
            $record = new AttendanceRecord(
                tenantId: $existing->getTenantId(),
                employeeId: $existing->getEmployeeId(),
                attendanceDate: $existing->getAttendanceDate(),
                checkIn: $checkIn,
                checkOut: $checkOut,
                breakDuration: $breakMins,
                workedMinutes: $workedMinutes,
                overtimeMinutes: $overtimeMinutes,
                status: $status,
                shiftId: $shiftId,
                remarks: $existing->getRemarks(),
                metadata: $existing->getMetadata(),
                createdAt: $existing->getCreatedAt(),
                updatedAt: $now,
                id: $existing->getId(),
            );
        } else {
            $record = new AttendanceRecord(
                tenantId: $dto->tenantId,
                employeeId: $dto->employeeId,
                attendanceDate: new \DateTimeImmutable($dto->attendanceDate),
                checkIn: $checkIn,
                checkOut: $checkOut,
                breakDuration: $breakMins,
                workedMinutes: $workedMinutes,
                overtimeMinutes: $overtimeMinutes,
                status: $status,
                shiftId: $shiftId,
                remarks: '',
                metadata: [],
                createdAt: $now,
                updatedAt: $now,
            );
        }

        $saved = $this->recordRepository->save($record);

        $this->addEvent(new AttendanceRecordProcessed($saved, $dto->tenantId));

        return $saved;
    }

    private function determineStatus(
        ?\DateTimeInterface $checkIn,
        mixed $shift,
        int $breakMins,
    ): AttendanceStatus {
        if ($checkIn === null) {
            return AttendanceStatus::ABSENT;
        }

        if ($shift === null) {
            return AttendanceStatus::PRESENT;
        }

        $shiftStart = new \DateTimeImmutable(
            (new \DateTimeImmutable($checkIn->format('Y-m-d').' '.$shift->getStartTime()))
                ->format(\DateTimeInterface::ATOM)
        );

        if ($checkIn->getTimestamp() - $shiftStart->getTimestamp() > $shift->getGraceMinutes() * 60) {
            return AttendanceStatus::LATE;
        }

        return AttendanceStatus::PRESENT;
    }
}

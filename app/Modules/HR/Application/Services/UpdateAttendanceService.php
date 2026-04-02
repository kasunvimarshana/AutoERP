<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use DateTimeImmutable;
use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\UpdateAttendanceServiceInterface;
use Modules\HR\Application\DTOs\UpdateAttendanceData;
use Modules\HR\Domain\Entities\Attendance;
use Modules\HR\Domain\Events\AttendanceUpdated;
use Modules\HR\Domain\Exceptions\AttendanceNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;

class UpdateAttendanceService extends BaseService implements UpdateAttendanceServiceInterface
{
    public function __construct(private readonly AttendanceRepositoryInterface $attendanceRepository)
    {
        parent::__construct($attendanceRepository);
    }

    protected function handle(array $data): Attendance
    {
        $dto        = UpdateAttendanceData::fromArray($data);
        $id         = (int) ($dto->id ?? 0);
        $attendance = $this->attendanceRepository->find($id);
        if (! $attendance) {
            throw new AttendanceNotFoundException($id);
        }

        $date         = $dto->isProvided('date') ? (string) $dto->date : $attendance->getDate();
        $checkInStr   = $dto->isProvided('check_in_time') ? (string) $dto->check_in_time : $attendance->getCheckInTime()->format('H:i:s');
        $checkInTime  = new DateTimeImmutable($date . ' ' . $checkInStr);
        $status       = $dto->isProvided('status') ? (string) $dto->status : $attendance->getStatus();
        $notes        = $dto->isProvided('notes') ? $dto->notes : $attendance->getNotes();
        $hoursWorked  = $dto->isProvided('hours_worked') ? $dto->hours_worked : $attendance->getHoursWorked();
        $checkOutTime = null;
        if ($dto->isProvided('check_out_time')) {
            $checkOutTime = $dto->check_out_time !== null
                ? new DateTimeImmutable($date . ' ' . $dto->check_out_time)
                : null;
        } else {
            $checkOutTime = $attendance->getCheckOutTime();
        }

        $attendance->updateDetails($date, $checkInTime, $status, $notes, $hoursWorked, $checkOutTime);

        $saved = $this->attendanceRepository->save($attendance);
        $this->addEvent(new AttendanceUpdated($saved));

        return $saved;
    }
}

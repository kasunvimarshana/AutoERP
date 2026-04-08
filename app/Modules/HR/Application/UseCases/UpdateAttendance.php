<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use DateTimeImmutable;
use Modules\HR\Application\DTOs\UpdateAttendanceData;
use Modules\HR\Domain\Entities\Attendance;
use Modules\HR\Domain\Events\AttendanceUpdated;
use Modules\HR\Domain\Exceptions\AttendanceNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;

class UpdateAttendance
{
    public function __construct(private readonly AttendanceRepositoryInterface $repo) {}

    public function execute(UpdateAttendanceData $data): Attendance
    {
        $id         = (int) ($data->id ?? 0);
        $attendance = $this->repo->find($id);
        if (! $attendance) {
            throw new AttendanceNotFoundException($id);
        }

        $date        = $data->isProvided('date') ? (string) $data->date : $attendance->getDate();
        $checkInStr  = $data->isProvided('check_in_time') ? (string) $data->check_in_time : $attendance->getCheckInTime()->format('H:i:s');
        $checkInTime = new DateTimeImmutable($date . ' ' . $checkInStr);
        $status      = $data->isProvided('status') ? (string) $data->status : $attendance->getStatus();
        $notes       = $data->isProvided('notes') ? $data->notes : $attendance->getNotes();
        $hoursWorked = $data->isProvided('hours_worked') ? $data->hours_worked : $attendance->getHoursWorked();

        if ($data->isProvided('check_out_time')) {
            $checkOutTime = $data->check_out_time !== null
                ? new DateTimeImmutable($date . ' ' . $data->check_out_time)
                : null;
        } else {
            $checkOutTime = $attendance->getCheckOutTime();
        }

        $attendance->updateDetails($date, $checkInTime, $status, $notes, $hoursWorked, $checkOutTime);

        $saved = $this->repo->save($attendance);
        AttendanceUpdated::dispatch($saved);

        return $saved;
    }
}

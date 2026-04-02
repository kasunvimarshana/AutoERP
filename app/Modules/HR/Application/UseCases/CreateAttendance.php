<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use DateTimeImmutable;
use Modules\HR\Application\DTOs\AttendanceData;
use Modules\HR\Domain\Entities\Attendance;
use Modules\HR\Domain\Events\AttendanceCreated;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;

class CreateAttendance
{
    public function __construct(private readonly AttendanceRepositoryInterface $repo) {}

    public function execute(AttendanceData $data): Attendance
    {
        $attendance = new Attendance(
            tenantId:    $data->tenant_id,
            employeeId:  $data->employee_id,
            date:        $data->date,
            checkInTime: new DateTimeImmutable($data->date . ' ' . $data->check_in_time),
            status:      $data->status,
            notes:       $data->notes,
            hoursWorked: $data->hours_worked,
        );

        if ($data->check_out_time !== null) {
            $attendance->checkOut(new DateTimeImmutable($data->date . ' ' . $data->check_out_time), $data->hours_worked);
        }

        $saved = $this->repo->save($attendance);
        AttendanceCreated::dispatch($saved);

        return $saved;
    }
}

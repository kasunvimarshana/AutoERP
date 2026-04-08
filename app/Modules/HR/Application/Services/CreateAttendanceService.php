<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use DateTimeImmutable;
use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\CreateAttendanceServiceInterface;
use Modules\HR\Application\DTOs\AttendanceData;
use Modules\HR\Domain\Entities\Attendance;
use Modules\HR\Domain\Events\AttendanceCreated;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;

class CreateAttendanceService extends BaseService implements CreateAttendanceServiceInterface
{
    public function __construct(private readonly AttendanceRepositoryInterface $attendanceRepository)
    {
        parent::__construct($attendanceRepository);
    }

    protected function handle(array $data): Attendance
    {
        $dto = AttendanceData::fromArray($data);

        $checkOutTime = $dto->check_out_time !== null
            ? new DateTimeImmutable($dto->date . ' ' . $dto->check_out_time)
            : null;

        $attendance = new Attendance(
            tenantId:     $dto->tenant_id,
            employeeId:   $dto->employee_id,
            date:         $dto->date,
            checkInTime:  new DateTimeImmutable($dto->date . ' ' . $dto->check_in_time),
            status:       $dto->status,
            notes:        $dto->notes,
            hoursWorked:  $dto->hours_worked,
        );

        if ($checkOutTime !== null) {
            $attendance->checkOut($checkOutTime, $dto->hours_worked);
        }

        $saved = $this->attendanceRepository->save($attendance);
        $this->addEvent(new AttendanceCreated($saved));

        return $saved;
    }
}

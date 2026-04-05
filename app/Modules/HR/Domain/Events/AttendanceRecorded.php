<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Events;

class AttendanceRecorded
{
    public function __construct(public readonly int $attendanceId, public readonly int $employeeId, public readonly string $source) {}
}

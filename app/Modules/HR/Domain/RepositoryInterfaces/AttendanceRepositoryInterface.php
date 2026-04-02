<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\Attendance;

interface AttendanceRepositoryInterface extends RepositoryInterface
{
    public function save(Attendance $attendance): Attendance;

    /**
     * Return all attendance records for a given employee.
     *
     * @return array<int, Attendance>
     */
    public function getByEmployee(int $employeeId): array;
}

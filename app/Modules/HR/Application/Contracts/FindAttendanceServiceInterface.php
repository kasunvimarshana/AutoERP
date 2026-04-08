<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindAttendanceServiceInterface extends ReadServiceInterface
{
    /**
     * @return array<int, \Modules\HR\Domain\Entities\Attendance>
     */
    public function getByEmployee(int $employeeId): array;
}

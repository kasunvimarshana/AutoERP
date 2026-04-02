<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindLeaveRequestServiceInterface extends ReadServiceInterface
{
    /**
     * Return all leave requests for a given employee.
     *
     * @return array<int, \Modules\HR\Domain\Entities\LeaveRequest>
     */
    public function getByEmployee(int $employeeId): array;

    /**
     * Return all pending leave requests for a given employee.
     *
     * @return array<int, \Modules\HR\Domain\Entities\LeaveRequest>
     */
    public function getPendingByEmployee(int $employeeId): array;
}

<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\LeaveRequest;

interface LeaveRequestRepositoryInterface extends RepositoryInterface
{
    public function save(LeaveRequest $leaveRequest): LeaveRequest;

    /**
     * Return all leave requests for a given employee.
     *
     * @return array<int, LeaveRequest>
     */
    public function getByEmployee(int $employeeId): array;

    /**
     * Return all pending leave requests for a given employee.
     *
     * @return array<int, LeaveRequest>
     */
    public function getPendingByEmployee(int $employeeId): array;
}

<?php

declare(strict_types=1);

namespace Modules\HR\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\HR\Entities\Leave;

/**
 * Leave Repository Interface
 *
 * Defines the contract for leave data access operations.
 */
interface LeaveRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get leaves by employee.
     */
    public function getByEmployee(int $employeeId): Collection;

    /**
     * Get leaves by status.
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get pending leaves.
     */
    public function getPendingLeaves(): Collection;

    /**
     * Get leaves by date range.
     */
    public function getByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Get employee leave balance.
     */
    public function getLeaveBalance(int $employeeId, int $leaveTypeId, int $year): float;
}

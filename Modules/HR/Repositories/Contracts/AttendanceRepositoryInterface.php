<?php

declare(strict_types=1);

namespace Modules\HR\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\HR\Entities\Attendance;

/**
 * Attendance Repository Interface
 *
 * Defines the contract for attendance data access operations.
 */
interface AttendanceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get attendance by employee and date.
     */
    public function getByEmployeeAndDate(int $employeeId, string $date): ?Attendance;

    /**
     * Get attendance records by employee.
     */
    public function getByEmployee(int $employeeId, ?string $startDate = null, ?string $endDate = null): Collection;

    /**
     * Get attendance by date range.
     */
    public function getByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Get incomplete attendance records.
     */
    public function getIncomplete(): Collection;
}

<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\AttendanceRecord;

interface AttendanceRecordRepositoryInterface extends RepositoryInterface
{
    public function save(AttendanceRecord $record): AttendanceRecord;

    public function find(int|string $id, array $columns = ['*']): ?AttendanceRecord;

    public function findByEmployeeAndDate(int $tenantId, int $employeeId, string $date): ?AttendanceRecord;

    /** @return AttendanceRecord[] */
    public function findByEmployeeAndMonth(int $tenantId, int $employeeId, int $year, int $month): array;
}

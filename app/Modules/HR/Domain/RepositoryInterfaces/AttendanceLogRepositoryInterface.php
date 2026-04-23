<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\AttendanceLog;

interface AttendanceLogRepositoryInterface extends RepositoryInterface
{
    public function save(AttendanceLog $log): AttendanceLog;

    public function find(int|string $id, array $columns = ['*']): ?AttendanceLog;

    /** @return AttendanceLog[] */
    public function findByEmployeeAndDate(int $tenantId, int $employeeId, string $date): array;
}

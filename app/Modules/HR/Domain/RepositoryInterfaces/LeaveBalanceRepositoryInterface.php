<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\LeaveBalance;

interface LeaveBalanceRepositoryInterface extends RepositoryInterface
{
    public function save(LeaveBalance $balance): LeaveBalance;

    public function find(int|string $id, array $columns = ['*']): ?LeaveBalance;

    public function findByEmployeeAndType(int $tenantId, int $employeeId, int $leaveTypeId, int $year): ?LeaveBalance;

    /** @return LeaveBalance[] */
    public function getBalancesForEmployee(int $tenantId, int $employeeId, int $year): array;
}

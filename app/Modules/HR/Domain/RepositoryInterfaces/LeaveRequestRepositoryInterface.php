<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\LeaveRequest;

interface LeaveRequestRepositoryInterface extends RepositoryInterface
{
    public function save(LeaveRequest $request): LeaveRequest;

    public function find(int|string $id, array $columns = ['*']): ?LeaveRequest;

    /** @return LeaveRequest[] */
    public function findOverlapping(int $tenantId, int $employeeId, string $startDate, string $endDate, ?int $excludeId = null): array;
}

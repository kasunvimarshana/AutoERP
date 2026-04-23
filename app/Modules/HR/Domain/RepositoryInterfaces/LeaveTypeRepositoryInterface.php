<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\LeaveType;

interface LeaveTypeRepositoryInterface extends RepositoryInterface
{
    public function save(LeaveType $leaveType): LeaveType;

    public function find(int|string $id, array $columns = ['*']): ?LeaveType;

    public function findByTenantAndCode(int $tenantId, string $code): ?LeaveType;
}

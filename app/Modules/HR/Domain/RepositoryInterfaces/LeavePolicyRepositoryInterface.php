<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\LeavePolicy;

interface LeavePolicyRepositoryInterface extends RepositoryInterface
{
    public function save(LeavePolicy $policy): LeavePolicy;

    public function find(int|string $id, array $columns = ['*']): ?LeavePolicy;
}

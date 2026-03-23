<?php

namespace Modules\User\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\User\Domain\Entities\Permission;

interface PermissionRepositoryInterface extends RepositoryInterface
{
    public function findByName(int $tenantId, string $name): ?Permission;
}

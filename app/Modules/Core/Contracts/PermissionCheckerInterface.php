<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface PermissionCheckerInterface
{
    public function allows(int $userId, int $tenantId, string $permission): bool;
}

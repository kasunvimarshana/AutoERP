<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface TenantUserAccessCheckerInterface
{
    public function isActiveTenantUser(int $userId, int $tenantId): bool;
}

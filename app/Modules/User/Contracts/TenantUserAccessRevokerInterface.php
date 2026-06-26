<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

interface TenantUserAccessRevokerInterface
{
    public function revokeSessionsForUser(int $tenantId, int $userId, string $reason): void;

    public function revokeAllForUser(int $tenantId, int $userId, string $reason): void;
}

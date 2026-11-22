<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Contracts;

interface TenantUserAccessRevokerInterface
{
    public function revokeSessionsForUser(int $tenantId, int $userId, string $reason): void;

    public function revokeAllForUser(int $tenantId, int $userId, string $reason): void;
}

<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Modules\Core\Contracts\PermissionCheckerInterface;

final class UserPermissionChecker implements PermissionCheckerInterface
{
    public function __construct(private readonly UserAccessResolver $access) {}

    public function allows(int $userId, int $tenantId, string $permission): bool
    {
        return $this->access->can($userId, $tenantId, $permission);
    }
}

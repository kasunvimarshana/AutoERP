<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Contracts\PlatformOperatorCheckerInterface;
use Modules\Tenant\Constants\TenantPermission;

final class TenantAuthorizationService
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly PermissionCheckerInterface $permissions,
        private readonly PlatformOperatorCheckerInterface $platformOperators,
    ) {}

    public function allows(string $permission): bool
    {
        $userId = $this->currentUser->currentUserId();
        $tenantId = $this->currentTenant->currentTenantId();
        if ($userId === null || $tenantId === null) {
            return false;
        }

        if (str_starts_with($permission, TenantPermission::PLATFORM_PREFIX)
            && ! $this->platformOperators->isPlatformOperator($userId)) {
            return false;
        }

        return $this->permissions->allows($userId, $tenantId, $permission);
    }
}

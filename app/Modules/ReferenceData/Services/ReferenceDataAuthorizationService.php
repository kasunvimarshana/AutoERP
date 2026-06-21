<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Services;

use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\ReferenceData\Constants\ReferenceDataPermission;

final class ReferenceDataAuthorizationService
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly PermissionCheckerInterface $permissions,
    ) {}

    public function canViewCurrent(): bool { return $this->allows(ReferenceDataPermission::VIEW); }
    public function canManageCurrent(): bool { return $this->allows(ReferenceDataPermission::MANAGE); }

    private function allows(string $permission): bool
    {
        $userId = $this->currentUser->currentUserId(); $tenantId = $this->currentTenant->currentTenantId();
        return $userId !== null && $tenantId !== null && $this->permissions->allows($userId, $tenantId, $permission);
    }
}

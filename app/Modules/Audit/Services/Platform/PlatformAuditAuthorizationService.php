<?php

declare(strict_types=1);

namespace Modules\Audit\Services\Platform;

use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\Core\Authorization\PlatformPermission;

final class PlatformAuditAuthorizationService
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly PlatformPermissionCheckerInterface $permissions,
    ) {}

    public function canView(): bool
    {
        $userId = $this->currentUser->currentUserId();

        return $userId !== null
            && $this->permissions->hasPermission($userId, PlatformPermission::AUDIT_VIEW);
    }

    public function canViewSensitive(): bool
    {
        $userId = $this->currentUser->currentUserId();

        return $userId !== null
            && $this->canView()
            && $this->permissions->hasPermission($userId, PlatformPermission::AUDIT_SENSITIVE_VIEW);
    }
}

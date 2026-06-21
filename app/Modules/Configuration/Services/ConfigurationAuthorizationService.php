<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Modules\Configuration\Constants\ConfigurationPermission;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;

final class ConfigurationAuthorizationService
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly PermissionCheckerInterface $permissions,
    ) {}

    public function canViewCurrent(): bool
    {
        return $this->allows(ConfigurationPermission::ENTRIES_VIEW);
    }

    public function canManageScopeCurrent(string $scope): bool
    {
        $permission = match ($scope) {
            ConfigurationScope::GLOBAL => ConfigurationPermission::ENTRIES_MANAGE_GLOBAL,
            ConfigurationScope::TENANT => ConfigurationPermission::ENTRIES_MANAGE_TENANT,
            ConfigurationScope::ORGANIZATION_UNIT => ConfigurationPermission::ENTRIES_MANAGE_ORGANIZATION,
            default => null,
        };

        return $permission !== null && $this->allows($permission);
    }

    public function canManageSensitiveCurrent(): bool
    {
        return $this->allows(ConfigurationPermission::ENTRIES_MANAGE_SENSITIVE);
    }

    private function allows(string $permission): bool
    {
        $userId = $this->currentUser->currentUserId();
        $tenantId = $this->currentTenant->currentTenantId();

        return $userId !== null
            && $tenantId !== null
            && $this->permissions->allows($userId, $tenantId, $permission);
    }
}

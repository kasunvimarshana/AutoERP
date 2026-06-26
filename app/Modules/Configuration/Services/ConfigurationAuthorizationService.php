<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Modules\Configuration\Constants\ConfigurationPermission;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\Core\Authorization\PlatformPermission;

final class ConfigurationAuthorizationService
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly PermissionCheckerInterface $permissions,
        private readonly PlatformPermissionCheckerInterface $platformPermissions,
    ) {}

    public function canViewScopeCurrent(string $scope): bool
    {
        if ($scope === ConfigurationScope::GLOBAL) {
            return $this->allowsPlatformPermission(PlatformPermission::CONFIGURATION_VIEW);
        }

        return $this->allowsTenantPermission(ConfigurationPermission::ENTRIES_VIEW);
    }

    public function canManageScopeCurrent(string $scope): bool
    {
        return match ($scope) {
            ConfigurationScope::GLOBAL => $this->allowsPlatformPermission(
                PlatformPermission::CONFIGURATION_MANAGE,
            ),
            ConfigurationScope::TENANT => $this->allowsTenantPermission(
                ConfigurationPermission::ENTRIES_MANAGE_TENANT,
            ),
            ConfigurationScope::ORGANIZATION_UNIT => $this->allowsTenantPermission(
                ConfigurationPermission::ENTRIES_MANAGE_ORGANIZATION,
            ),
            default => false,
        };
    }

    public function canManageSensitiveCurrent(string $scope): bool
    {
        return $scope === ConfigurationScope::GLOBAL
            ? $this->allowsPlatformPermission(PlatformPermission::CONFIGURATION_MANAGE)
                && $this->allowsPlatformPermission(PlatformPermission::SECRETS_MANAGE)
            : $this->allowsTenantPermission(ConfigurationPermission::ENTRIES_MANAGE_SENSITIVE);
    }

    public function canViewPlatformScope(string $scope): bool
    {
        return in_array($scope, ConfigurationScope::values(), true)
            && $this->allowsPlatformPermission(PlatformPermission::CONFIGURATION_VIEW);
    }

    public function canManagePlatformScope(string $scope): bool
    {
        return in_array($scope, ConfigurationScope::values(), true)
            && $this->allowsPlatformPermission(PlatformPermission::CONFIGURATION_MANAGE);
    }

    public function canManagePlatformSensitive(): bool
    {
        return $this->allowsPlatformPermission(PlatformPermission::CONFIGURATION_MANAGE)
            && $this->allowsPlatformPermission(PlatformPermission::SECRETS_MANAGE);
    }

    private function allowsPlatformPermission(string $permission): bool
    {
        $userId = $this->currentUser->currentUserId();

        return $userId !== null
            && $this->platformPermissions->allows($userId, $permission);
    }

    private function allowsTenantPermission(string $permission): bool
    {
        $userId = $this->currentUser->currentUserId();
        $tenantId = $this->currentTenant->currentTenantId();

        return $userId !== null
            && $tenantId !== null
            && $this->permissions->allows($userId, $tenantId, $permission);
    }
}

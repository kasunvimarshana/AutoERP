<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Modules\Configuration\Constants\ConfigurationPermission;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Contracts\PlatformOperatorCheckerInterface;

final class ConfigurationAuthorizationService
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly PermissionCheckerInterface $permissions,
        private readonly PlatformOperatorCheckerInterface $platformOperators,
    ) {}

    public function canViewCurrent(): bool
    {
        return $this->allows(ConfigurationPermission::ENTRIES_VIEW);
    }

    public function canViewScopeCurrent(string $scope): bool
    {
        return $scope === ConfigurationScope::GLOBAL
            ? $this->canViewPlatformDefaultsCurrent()
            : $this->canViewCurrent();
    }

    public function canManageScopeCurrent(string $scope): bool
    {
        $permission = match ($scope) {
            ConfigurationScope::GLOBAL => ConfigurationPermission::PLATFORM_DEFAULTS_MANAGE,
            ConfigurationScope::TENANT => ConfigurationPermission::ENTRIES_MANAGE_TENANT,
            ConfigurationScope::ORGANIZATION_UNIT => ConfigurationPermission::ENTRIES_MANAGE_ORGANIZATION,
            default => null,
        };

        return $permission !== null
            && ($scope !== ConfigurationScope::GLOBAL || $this->isPlatformOperator())
            && $this->allows($permission);
    }

    public function canManageSensitiveCurrent(string $scope): bool
    {
        if ($scope === ConfigurationScope::GLOBAL) {
            return $this->isPlatformOperator()
                && $this->allows(ConfigurationPermission::PLATFORM_DEFAULTS_MANAGE_SENSITIVE);
        }

        return $this->allows(ConfigurationPermission::ENTRIES_MANAGE_SENSITIVE);
    }

    public function canViewPlatformDefaultsCurrent(): bool
    {
        return $this->isPlatformOperator()
            && $this->allows(ConfigurationPermission::PLATFORM_DEFAULTS_VIEW);
    }

    private function isPlatformOperator(): bool
    {
        $userId = $this->currentUser->currentUserId();

        return $userId !== null && $this->platformOperators->isPlatformOperator($userId);
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

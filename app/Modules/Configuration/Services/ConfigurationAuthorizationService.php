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

    public function canViewScopeCurrent(string $scope): bool
    {
        if ($scope === ConfigurationScope::GLOBAL) {
            return $this->isPlatformOperator();
        }

        return $this->allowsTenantPermission(ConfigurationPermission::ENTRIES_VIEW);
    }

    public function canManageScopeCurrent(string $scope): bool
    {
        return match ($scope) {
            ConfigurationScope::GLOBAL => $this->isPlatformOperator(),
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
            ? $this->isPlatformOperator()
            : $this->allowsTenantPermission(ConfigurationPermission::ENTRIES_MANAGE_SENSITIVE);
    }

    private function isPlatformOperator(): bool
    {
        $userId = $this->currentUser->currentUserId();

        return $userId !== null && $this->platformOperators->isPlatformOperator($userId);
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

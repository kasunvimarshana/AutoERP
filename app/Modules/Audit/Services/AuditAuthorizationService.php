<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\Audit\Constants\AuditPermission;
use Modules\Audit\Data\AuditReadScope;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;

final class AuditAuthorizationService
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly PermissionCheckerInterface $permissions,
    ) {}

    public function canViewCurrent(): bool
    {
        $userId = $this->currentUser->currentUserId();
        $tenantId = $this->currentTenant->currentTenantId();

        return $userId !== null
            && $tenantId !== null
            && $this->permissions->allows($userId, $tenantId, AuditPermission::LOGS_VIEW);
    }

    public function resolveReadScope(): AuditReadScope
    {
        $userId = $this->currentUser->currentUserId();
        $tenantId = $this->currentTenant->currentTenantId();
        if ($userId === null || $tenantId === null || ! $this->canViewCurrent()) {
            throw new AuthorizationException('Viewing audit logs is not authorized.');
        }

        $tenantWide = $this->permissions->allows($userId, $tenantId, AuditPermission::LOGS_VIEW_TENANT);
        $organizationUnitId = $this->currentOrganizationUnit->currentOrganizationUnitId();

        if (! $tenantWide && $organizationUnitId === null) {
            throw new AuthorizationException('Tenant-wide audit permission is required without an organization-unit context.');
        }

        return new AuditReadScope($tenantId, $organizationUnitId, $tenantWide);
    }

    public function canViewSensitiveCurrent(): bool
    {
        $userId = $this->currentUser->currentUserId();
        $tenantId = $this->currentTenant->currentTenantId();

        return $userId !== null
            && $tenantId !== null
            && $this->canViewCurrent()
            && $this->permissions->allows($userId, $tenantId, AuditPermission::LOGS_VIEW_SENSITIVE);
    }
}

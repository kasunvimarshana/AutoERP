<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Support;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Exceptions\CurrentOrganizationUnitContextResolutionException;

final class OrganizationUnitContext
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
    ) {
    }

    public function currentTenantId(): ?int
    {
        return $this->currentTenant->currentTenantId();
    }

    public function requireTenantId(): int
    {
        $tenantId = $this->currentTenantId();
        if ($tenantId === null || $tenantId < 1) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'Tenant context is required for organization unit operations.',
            );
        }

        return $tenantId;
    }

    public function resolveTenantId(?int $candidateTenantId): int
    {
        $tenantId = $this->requireTenantId();

        if ($candidateTenantId === null || $candidateTenantId < 1) {
            return $tenantId;
        }

        if ($candidateTenantId !== $tenantId) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'Tenant scope mismatch for organization unit operation.',
            );
        }

        return $tenantId;
    }

    public function currentOrganizationUnitId(): ?int
    {
        return $this->currentOrganizationUnit->currentOrganizationUnitId();
    }
}

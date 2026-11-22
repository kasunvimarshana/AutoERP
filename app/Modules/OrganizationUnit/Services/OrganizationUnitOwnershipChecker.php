<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services;

use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\OrganizationUnit\Contracts\OrganizationUnitOwnershipCheckerInterface;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

final class OrganizationUnitOwnershipChecker implements OrganizationUnitOwnershipCheckerInterface
{
    public function __construct(
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function belongsToTenant(int $organizationUnitId, int $tenantId): bool
    {
        return $this->executionContext->runForTenant(
            $tenantId,
            fn (): bool => OrganizationUnitModel::query()
                ->whereKey($organizationUnitId)
                ->where('tenant_id', $tenantId)
                ->exists(),
        );
    }

    public function belongsToActiveTenant(int $organizationUnitId, int $tenantId): bool
    {
        return $this->executionContext->runForTenant(
            $tenantId,
            fn (): bool => OrganizationUnitModel::query()
                ->whereKey($organizationUnitId)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereNull('retired_at')
                ->exists(),
        );
    }
}

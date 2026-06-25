<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services;

use Modules\OrganizationUnit\Contracts\OrganizationUnitOwnershipCheckerInterface;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

final class OrganizationUnitOwnershipChecker implements OrganizationUnitOwnershipCheckerInterface
{
    public function belongsToTenant(int $organizationUnitId, int $tenantId): bool
    {
        return OrganizationUnitModel::query()
            ->whereKey($organizationUnitId)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function belongsToActiveTenant(int $organizationUnitId, int $tenantId): bool
    {
        return OrganizationUnitModel::query()
            ->whereKey($organizationUnitId)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->exists();
    }
}

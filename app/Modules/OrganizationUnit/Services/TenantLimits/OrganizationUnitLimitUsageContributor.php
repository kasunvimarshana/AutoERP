<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\TenantLimits;

use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Services\Contracts\TenantLimitUsageContributorInterface;

final class OrganizationUnitLimitUsageContributor implements TenantLimitUsageContributorInterface
{
    public function __construct(private readonly OrganizationUnitModel $organizationUnits) {}

    public function usage(int $tenantId): array
    {
        return [
            'max_organization_units' => $this->organizationUnits->newQuery()
                ->where('tenant_id', $tenantId)
                ->whereNull('retired_at')
                ->count(),
        ];
    }
}

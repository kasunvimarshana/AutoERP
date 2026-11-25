<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\OrganizationUnit\Services\TenantLimits;

use Modules\Core\Contracts\TenantLimitUsageContributorInterface;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

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

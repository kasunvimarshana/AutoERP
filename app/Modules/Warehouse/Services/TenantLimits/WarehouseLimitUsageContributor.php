<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\TenantLimits;

use Modules\Core\Tenancy\TenantPlanLimit;

use Modules\Core\Contracts\TenantLimitUsageContributorInterface;
use Modules\Warehouse\Models\WarehouseModel;

final class WarehouseLimitUsageContributor implements TenantLimitUsageContributorInterface
{
    public function __construct(private readonly WarehouseModel $warehouses) {}

    public function usage(int $tenantId): array
    {
        return [
            TenantPlanLimit::WAREHOUSES => $this->warehouses->newQuery()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->count(),
        ];
    }
}

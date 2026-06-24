<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\TenantLimits;

use Modules\Tenant\Services\Contracts\TenantLimitUsageContributorInterface;
use Modules\Warehouse\Models\WarehouseModel;

final class WarehouseLimitUsageContributor implements TenantLimitUsageContributorInterface
{
    public function __construct(private readonly WarehouseModel $warehouses) {}

    public function usage(int $tenantId): array
    {
        return [
            'max_warehouses' => $this->warehouses->newQuery()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->count(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Subscriptions;

use Modules\Core\Tenancy\TenantPlanLimit;

use Modules\Tenant\Models\TenantDocumentModel;
use Modules\Core\Contracts\TenantLimitUsageContributorInterface;

final class TenantStorageLimitUsageContributor implements TenantLimitUsageContributorInterface
{
    private const BYTES_PER_MEGABYTE = 1_048_576;

    public function __construct(private readonly TenantDocumentModel $documents) {}

    public function usage(int $tenantId): array
    {
        $bytes = (int) $this->documents->newQuery()
            ->where('tenant_id', $tenantId)
            ->sum('size_bytes');

        return [
            TenantPlanLimit::STORAGE_MEGABYTES => (int) ceil($bytes / self::BYTES_PER_MEGABYTE),
        ];
    }
}

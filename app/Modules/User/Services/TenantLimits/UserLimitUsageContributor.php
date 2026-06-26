<?php

declare(strict_types=1);

namespace Modules\User\Services\TenantLimits;

use Modules\Core\Tenancy\TenantPlanLimit;
use Modules\Core\Contracts\TenantLimitUsageContributorInterface;
use Modules\User\Models\UserModel;

final class UserLimitUsageContributor implements TenantLimitUsageContributorInterface
{
    public function __construct(private readonly UserModel $users) {}

    public function usage(int $tenantId): array
    {
        return [
            TenantPlanLimit::USERS => $this->users->newQuery()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->count(),
        ];
    }
}

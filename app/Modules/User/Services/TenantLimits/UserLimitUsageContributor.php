<?php

declare(strict_types=1);

namespace Modules\User\Services\TenantLimits;

use Modules\Tenant\Services\Contracts\TenantLimitUsageContributorInterface;
use Modules\User\Models\UserModel;

final class UserLimitUsageContributor implements TenantLimitUsageContributorInterface
{
    public function __construct(private readonly UserModel $users) {}

    public function usage(int $tenantId): array
    {
        return [
            'max_users' => $this->users->newQuery()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->count(),
        ];
    }
}

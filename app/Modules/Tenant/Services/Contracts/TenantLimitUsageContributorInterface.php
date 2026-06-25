<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

interface TenantLimitUsageContributorInterface
{
    /** @return array<string, int> */
    public function usage(int $tenantId): array;
}

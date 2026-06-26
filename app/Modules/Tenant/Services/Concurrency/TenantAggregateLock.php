<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Concurrency;

use Modules\Core\Contracts\TenantAggregateLockInterface;
use Modules\Tenant\Models\TenantModel;
use RuntimeException;

final class TenantAggregateLock implements TenantAggregateLockInterface
{
    public function lock(int $tenantId): void
    {
        if ($tenantId < 1 || TenantModel::query()->whereKey($tenantId)->lockForUpdate()->first(['id']) === null) {
            throw new RuntimeException('Tenant not found.');
        }
    }
}

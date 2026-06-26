<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface TenantAggregateLockInterface
{
    public function lock(int $tenantId): void;
}

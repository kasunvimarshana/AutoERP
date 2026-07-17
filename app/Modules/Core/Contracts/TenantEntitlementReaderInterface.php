<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface TenantEntitlementReaderInterface
{
    public function featureEnabled(int $tenantId, string $feature): bool;

    public function limit(int $tenantId, string $limit): ?int;
}

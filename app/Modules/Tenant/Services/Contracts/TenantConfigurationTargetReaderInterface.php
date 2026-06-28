<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

use Modules\Tenant\Data\TenantConfigurationTarget;

interface TenantConfigurationTargetReaderInterface
{
    public function find(int $tenantId): ?TenantConfigurationTarget;

    public function count(): int;
}

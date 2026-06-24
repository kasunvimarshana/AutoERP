<?php

declare(strict_types=1);

namespace Modules\Tenant\Queue;

interface TenantAwareJobInterface
{
    public function tenantJobContext(): TenantJobContext;
}

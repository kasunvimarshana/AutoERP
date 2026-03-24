<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Events;

class TenantDeleted
{
    public function __construct(public int $tenantId) {}
}

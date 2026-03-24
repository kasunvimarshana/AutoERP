<?php

namespace Modules\Tenant\Domain\Events;

class TenantDeleted
{
    public function __construct(public int $tenantId) {}
}

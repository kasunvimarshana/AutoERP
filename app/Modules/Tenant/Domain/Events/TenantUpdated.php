<?php

namespace Modules\Tenant\Domain\Events;

use Modules\Tenant\Domain\Entities\Tenant;

class TenantUpdated
{
    public function __construct(public Tenant $tenant) {}
}

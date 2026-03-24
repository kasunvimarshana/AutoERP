<?php

namespace Modules\Tenant\Domain\Events;

use Modules\Tenant\Domain\Entities\Tenant;

class TenantConfigChanged
{
    public function __construct(public Tenant $tenant) {}
}

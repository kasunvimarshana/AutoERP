<?php

namespace Modules\Core\Events;

use Modules\Core\Models\Tenant;

class TenantCreated extends BaseEvent
{
    public function __construct(public Tenant $tenant)
    {
        parent::__construct();
    }
}

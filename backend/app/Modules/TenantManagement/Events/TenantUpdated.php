<?php

namespace App\Modules\TenantManagement\Events;

use App\Modules\TenantManagement\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Tenant $tenant)
    {
    }
}

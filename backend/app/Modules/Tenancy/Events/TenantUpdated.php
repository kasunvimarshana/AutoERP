<?php

namespace App\Modules\Tenancy\Events;

use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Tenant $tenant)
    {
    }
}

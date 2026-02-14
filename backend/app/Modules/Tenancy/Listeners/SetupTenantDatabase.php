<?php

namespace App\Modules\Tenancy\Listeners;

use App\Modules\Tenancy\Events\TenantCreated;
use Illuminate\Support\Facades\Log;

class SetupTenantDatabase
{
    public function handle(TenantCreated $event): void
    {
        Log::info('Setting up database for tenant', ['tenant_id' => $event->tenant->id]);
        
        // Logic to create tenant database and run migrations
        // This would be implemented based on specific requirements
    }
}

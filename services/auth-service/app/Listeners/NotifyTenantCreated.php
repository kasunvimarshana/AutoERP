<?php

namespace App\Listeners;

use App\Domain\Events\TenantCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyTenantCreated implements ShouldQueue
{
    public function handle(TenantCreated $event): void
    {
        Log::info('New tenant provisioned', [
            'tenant_id'   => $event->tenant->id,
            'subdomain'   => $event->tenant->subdomain,
            'admin_user'  => $event->adminUserId,
        ]);
    }
}

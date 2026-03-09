<?php

namespace App\Listeners;

use App\Domain\Events\UserLoggedIn;
use Illuminate\Support\Facades\Log;

class UpdateLoginMetrics
{
    public function handle(UserLoggedIn $event): void
    {
        Log::info('User logged in', [
            'user_id'   => $event->user->id,
            'tenant_id' => $event->tenantId,
            'ip'        => $event->ipAddress,
            'device_id' => $event->deviceId,
        ]);
    }
}

<?php

namespace App\Modules\AuthManagement\Listeners;

use App\Modules\AuthManagement\Events\UserLoggedIn;
use App\Modules\AuthManagement\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Log;

class LogUserLogin
{
    /**
     * Handle the event.
     */
    public function handle(UserLoggedIn $event): void
    {
        try {
            SecurityAuditLog::logEvent(
                SecurityAuditLog::EVENT_LOGIN_SUCCESS,
                $event->user->id,
                $event->user->tenant_id,
                "User {$event->user->email} logged in successfully",
                SecurityAuditLog::SEVERITY_INFO,
                [
                    'ip_address' => $event->ipAddress,
                    'user_agent' => $event->userAgent,
                ]
            );

            Log::info('User logged in', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'ip_address' => $event->ipAddress,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log user login', ['error' => $e->getMessage()]);
        }
    }
}

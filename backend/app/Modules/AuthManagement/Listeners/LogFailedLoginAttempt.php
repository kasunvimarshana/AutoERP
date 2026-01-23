<?php

namespace App\Modules\AuthManagement\Listeners;

use App\Modules\AuthManagement\Events\LoginAttemptFailed;
use App\Modules\AuthManagement\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LogFailedLoginAttempt
{
    /**
     * Handle the event.
     */
    public function handle(LoginAttemptFailed $event): void
    {
        try {
            $user = User::where('email', $event->email)->first();

            SecurityAuditLog::logEvent(
                SecurityAuditLog::EVENT_LOGIN_FAILED,
                $user?->id,
                $user?->tenant_id,
                "Failed login attempt for {$event->email}: {$event->reason}",
                SecurityAuditLog::SEVERITY_WARNING,
                [
                    'email' => $event->email,
                    'reason' => $event->reason,
                ]
            );

            Log::warning('Failed login attempt', [
                'email' => $event->email,
                'ip_address' => $event->ipAddress,
                'reason' => $event->reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log failed login attempt', ['error' => $e->getMessage()]);
        }
    }
}

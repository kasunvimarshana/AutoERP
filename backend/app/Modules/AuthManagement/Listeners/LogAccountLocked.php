<?php

namespace App\Modules\AuthManagement\Listeners;

use App\Modules\AuthManagement\Events\UserAccountLocked;
use App\Modules\AuthManagement\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Log;

class LogAccountLocked
{
    /**
     * Handle the event.
     */
    public function handle(UserAccountLocked $event): void
    {
        try {
            SecurityAuditLog::logEvent(
                SecurityAuditLog::EVENT_ACCOUNT_LOCKED,
                $event->user->id,
                $event->user->tenant_id,
                "Account locked for user {$event->user->email}: {$event->reason}",
                SecurityAuditLog::SEVERITY_CRITICAL,
                ['reason' => $event->reason]
            );

            Log::critical('User account locked', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'reason' => $event->reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log account lock', ['error' => $e->getMessage()]);
        }
    }
}

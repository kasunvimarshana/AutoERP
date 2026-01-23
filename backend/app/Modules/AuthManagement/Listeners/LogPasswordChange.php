<?php

namespace App\Modules\AuthManagement\Listeners;

use App\Modules\AuthManagement\Events\PasswordChanged;
use App\Modules\AuthManagement\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Log;

class LogPasswordChange
{
    /**
     * Handle the event.
     */
    public function handle(PasswordChanged $event): void
    {
        try {
            SecurityAuditLog::logEvent(
                SecurityAuditLog::EVENT_PASSWORD_CHANGE,
                $event->user->id,
                $event->user->tenant_id,
                "User {$event->user->email} changed their password",
                SecurityAuditLog::SEVERITY_INFO
            );

            Log::info('Password changed', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log password change', ['error' => $e->getMessage()]);
        }
    }
}

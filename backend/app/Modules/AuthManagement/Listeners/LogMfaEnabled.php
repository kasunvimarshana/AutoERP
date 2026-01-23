<?php

namespace App\Modules\AuthManagement\Listeners;

use App\Modules\AuthManagement\Events\MfaEnabled;
use App\Modules\AuthManagement\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Log;

class LogMfaEnabled
{
    /**
     * Handle the event.
     */
    public function handle(MfaEnabled $event): void
    {
        try {
            SecurityAuditLog::logEvent(
                SecurityAuditLog::EVENT_MFA_ENABLED,
                $event->user->id,
                $event->user->tenant_id,
                "MFA ({$event->mfaType}) enabled for user {$event->user->email}",
                SecurityAuditLog::SEVERITY_INFO,
                ['mfa_type' => $event->mfaType]
            );

            Log::info('MFA enabled', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'mfa_type' => $event->mfaType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log MFA enablement', ['error' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Modules\AuthManagement\Listeners;

use App\Modules\AuthManagement\Events\SuspiciousActivityDetected;
use App\Modules\AuthManagement\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Log;

class LogSuspiciousActivity
{
    /**
     * Handle the event.
     */
    public function handle(SuspiciousActivityDetected $event): void
    {
        try {
            SecurityAuditLog::logEvent(
                SecurityAuditLog::EVENT_SUSPICIOUS_ACTIVITY,
                $event->userId,
                $event->metadata['tenant_id'] ?? null,
                "Suspicious activity detected: {$event->activityType}",
                SecurityAuditLog::SEVERITY_CRITICAL,
                $event->metadata
            );

            Log::critical('Suspicious activity detected', [
                'user_id' => $event->userId,
                'activity_type' => $event->activityType,
                'metadata' => $event->metadata,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log suspicious activity', ['error' => $e->getMessage()]);
        }
    }
}

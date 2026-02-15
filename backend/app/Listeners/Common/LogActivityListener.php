<?php

declare(strict_types=1);

namespace App\Listeners\Common;

use App\Core\Events\BaseEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Log Activity Listener
 * 
 * Logs all domain events to activity log for auditing
 */
class LogActivityListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Handle the event
     */
    public function handle(BaseEvent $event): void
    {
        try {
            // Log to activity log (you can extend this to store in database)
            Log::channel('activity')->info('Domain Event Occurred', [
                'event' => $event->getEventName(),
                'tenant_id' => $event->tenantId,
                'user_id' => $event->userId,
                'occurred_at' => $event->occurredAt?->format('Y-m-d H:i:s'),
                'data' => $event->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log activity', [
                'event' => $event->getEventName(),
                'error' => $e->getMessage(),
            ]);
            
            // Don't fail the entire event processing if logging fails
            // But report for monitoring
            report($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(BaseEvent $event, \Throwable $exception): void
    {
        Log::error('LogActivityListener failed', [
            'event' => $event->getEventName(),
            'tenant_id' => $event->tenantId,
            'error' => $exception->getMessage(),
        ]);
    }
}

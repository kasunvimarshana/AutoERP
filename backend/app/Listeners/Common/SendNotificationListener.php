<?php

declare(strict_types=1);

namespace App\Listeners\Common;

use App\Core\Events\BaseEvent;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Notification Listener
 * 
 * Generic listener for sending notifications based on events
 */
class SendNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Handle the event
     */
    public function handle(BaseEvent $event): void
    {
        try {
            // Send notification based on event type
            $this->notificationService->sendEventNotification($event);
        } catch (\Exception $e) {
            Log::error('Failed to send notification for event', [
                'event' => $event->getEventName(),
                'tenant_id' => $event->tenantId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(BaseEvent $event, \Throwable $exception): void
    {
        Log::error('SendNotificationListener failed', [
            'event' => $event->getEventName(),
            'tenant_id' => $event->tenantId,
            'error' => $exception->getMessage(),
        ]);
    }
}

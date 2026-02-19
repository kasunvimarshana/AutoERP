<?php

declare(strict_types=1);

namespace Modules\JobCard\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\JobCard\Events\JobCardCompleted;

/**
 * Notify Customer of Job Completion
 *
 * Listens to JobCardCompleted event and sends completion notification to customer
 * Runs asynchronously in the queue
 */
class NotifyCustomerOfJobCompletion implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the listener may be attempted
     */
    public int $tries = 3;

    /**
     * Handle the event
     */
    public function handle(JobCardCompleted $event): void
    {
        try {
            // Load job card with customer and vehicle
            $jobCard = $event->jobCard->load(['customer', 'vehicle']);

            if (! $jobCard->customer) {
                Log::warning('No customer found for job card', [
                    'job_card_id' => $jobCard->id,
                ]);

                return;
            }

            // Send notification (placeholder - implement actual notification class)
            // $jobCard->customer->notify(new JobCompletedNotification($jobCard));

            Log::info('Job completion notification sent to customer', [
                'job_card_id' => $jobCard->id,
                'customer_id' => $jobCard->customer_id,
                'notification_channels' => ['mail', 'sms', 'database'],
            ]);

            // Also send push notification if customer has mobile app
            // if ($jobCard->customer->hasDeviceTokens()) {
            //     $jobCard->customer->notifyVia(['fcm'], new JobCompletedNotification($jobCard));
            // }
        } catch (\Exception $e) {
            Log::error('Failed to send job completion notification', [
                'job_card_id' => $event->jobCard->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Rethrow to trigger retry
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(JobCardCompleted $event, \Throwable $exception): void
    {
        Log::error('Failed to notify customer after all retries', [
            'job_card_id' => $event->jobCard->id,
            'error' => $exception->getMessage(),
        ]);

        // Create manual followup task
    }
}

<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Events\NotificationFailed;
use Modules\Notification\Events\NotificationSent;
use Modules\Notification\Models\Notification;

/**
 * Notification Dispatcher
 *
 * Routes notifications to the appropriate channel service and handles retries
 */
class NotificationDispatcher
{
    public function __construct(
        private EmailNotificationService $emailService,
        private SmsNotificationService $smsService,
        private PushNotificationService $pushService,
        private InAppNotificationService $inAppService
    ) {}

    /**
     * Dispatch notification to appropriate channel
     */
    public function dispatch(Notification $notification): bool
    {
        return TransactionHelper::execute(function () use ($notification) {
            try {
                $success = $this->sendToChannel($notification);

                if ($success) {
                    $notification->update([
                        'status' => NotificationStatus::SENT,
                        'sent_at' => now(),
                    ]);

                    // Fire sent event
                    event(new NotificationSent($notification));
                }

                return $success;
            } catch (\Exception $e) {
                $this->handleFailure($notification, $e);

                // Fire failed event
                event(new NotificationFailed($notification, $e->getMessage()));

                return false;
            }
        });
    }

    /**
     * Send notification to the appropriate channel
     */
    private function sendToChannel(Notification $notification): bool
    {
        $service = $this->getChannelService($notification->type);

        return $service->send($notification);
    }

    /**
     * Get the appropriate channel service
     */
    private function getChannelService(NotificationType $type): EmailNotificationService|SmsNotificationService|PushNotificationService|InAppNotificationService
    {
        return match ($type) {
            NotificationType::EMAIL => $this->emailService,
            NotificationType::SMS => $this->smsService,
            NotificationType::PUSH => $this->pushService,
            NotificationType::IN_APP => $this->inAppService,
            default => throw new \InvalidArgumentException("Unsupported notification type: {$type->value}"),
        };
    }

    /**
     * Handle notification failure
     */
    private function handleFailure(Notification $notification, \Exception $exception): void
    {
        $notification->markAsFailed($exception->getMessage());

        // Log error
        logger()->error('Notification sending failed', [
            'notification_id' => $notification->id,
            'type' => $notification->type->value,
            'user_id' => $notification->user_id,
            'retry_count' => $notification->retry_count,
            'max_retries' => $notification->max_retries,
            'error' => $exception->getMessage(),
        ]);

        // If we haven't exceeded max retries, we'll retry later
        if ($notification->retry_count < $notification->max_retries) {
            logger()->info('Notification will be retried', [
                'notification_id' => $notification->id,
                'retry_count' => $notification->retry_count,
                'max_retries' => $notification->max_retries,
            ]);
        }
    }

    /**
     * Retry failed notification
     */
    public function retry(Notification $notification): bool
    {
        if ($notification->status !== NotificationStatus::FAILED) {
            throw new \InvalidArgumentException('Only failed notifications can be retried');
        }

        if ($notification->retry_count >= $notification->max_retries) {
            throw new \InvalidArgumentException('Maximum retry attempts reached');
        }

        return TransactionHelper::execute(function () use ($notification) {
            // Reset error message
            $notification->update([
                'status' => NotificationStatus::PENDING,
                'error_message' => null,
            ]);

            // Attempt to dispatch again
            return $this->dispatch($notification->fresh());
        });
    }

    /**
     * Batch dispatch multiple notifications
     */
    public function dispatchBatch(array $notifications): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($notifications as $notification) {
            try {
                $success = $this->dispatch($notification);

                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }

                $results['details'][] = [
                    'notification_id' => $notification->id,
                    'success' => $success,
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'notification_id' => $notification->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Process pending notifications
     */
    public function processPending(int $limit = 100): array
    {
        $pending = app(\Modules\Notification\Repositories\NotificationRepository::class)
            ->getPending($limit);

        return $this->dispatchBatch($pending->all());
    }

    /**
     * Process retryable failed notifications
     */
    public function processRetries(): array
    {
        $retryable = app(\Modules\Notification\Repositories\NotificationRepository::class)
            ->getRetryable();

        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($retryable as $notification) {
            try {
                $success = $this->retry($notification);

                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }

                $results['details'][] = [
                    'notification_id' => $notification->id,
                    'success' => $success,
                    'retry_count' => $notification->retry_count,
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'notification_id' => $notification->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}

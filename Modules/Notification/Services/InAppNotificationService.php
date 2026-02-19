<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Models\Notification;
use Modules\Notification\Repositories\NotificationLogRepository;

/**
 * In-App Notification Service
 *
 * Handles in-app notifications which are already stored in the database.
 * This service just marks them as sent since they're already available to the user.
 */
class InAppNotificationService
{
    public function __construct(
        private NotificationLogRepository $logRepository
    ) {}

    /**
     * Send in-app notification
     *
     * For in-app notifications, the notification is already stored in the database.
     * This method simply marks it as sent/delivered since it's immediately available.
     */
    public function send(Notification $notification): bool
    {
        try {
            // In-app notifications are already in the database
            // Just mark as sent and log it
            $notification->markAsSent();
            $notification->markAsDelivered();

            // Log success
            $this->logSuccess($notification);

            return true;
        } catch (\Exception $e) {
            // Log failure
            $this->logFailure($notification, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Log successful delivery
     */
    private function logSuccess(Notification $notification): void
    {
        $this->logRepository->create([
            'tenant_id' => $notification->tenant_id,
            'organization_id' => $notification->organization_id,
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'type' => $notification->type,
            'status' => NotificationStatus::DELIVERED,
            'channel' => 'in_app',
            'recipient' => (string) $notification->user_id,
            'subject' => $notification->subject,
            'sent_at' => now(),
            'delivered_at' => now(),
            'metadata' => [
                'sent_via' => 'in_app',
                'stored_in_db' => true,
            ],
        ]);
    }

    /**
     * Log failed delivery
     */
    private function logFailure(Notification $notification, string $errorMessage): void
    {
        $this->logRepository->create([
            'tenant_id' => $notification->tenant_id,
            'organization_id' => $notification->organization_id,
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'type' => $notification->type,
            'status' => NotificationStatus::FAILED,
            'channel' => 'in_app',
            'recipient' => (string) $notification->user_id,
            'subject' => $notification->subject,
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'metadata' => [
                'sent_via' => 'in_app',
                'stored_in_db' => true,
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Notification\Enums\NotificationPriority;
use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Events\NotificationRead;
use Modules\Notification\Models\Notification;
use Modules\Notification\Repositories\NotificationRepository;
use Modules\Notification\Repositories\NotificationTemplateRepository;

/**
 * Notification Service
 *
 * Main orchestration service for sending and managing notifications
 */
class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private NotificationTemplateRepository $templateRepository,
        private TemplateService $templateService,
        private NotificationDispatcher $dispatcher
    ) {}

    /**
     * Send a notification to a user
     */
    public function send(
        int $userId,
        string $templateCode,
        array $data = [],
        ?NotificationType $type = null,
        ?NotificationPriority $priority = null
    ): Notification {
        return TransactionHelper::execute(function () use ($userId, $templateCode, $data, $type, $priority) {
            // Get template
            $template = $this->templateRepository->findByCode($templateCode);

            // Validate data against template
            $this->templateService->validate($templateCode, $data);

            // Render template
            $rendered = $this->templateService->render($templateCode, $data);

            // Get tenant and organization from auth context
            $tenantId = auth()->user()?->tenant_id ?? null;
            $organizationId = auth()->user()?->organization_id ?? null;

            // Create notification
            $notification = $this->notificationRepository->create([
                'tenant_id' => $tenantId,
                'organization_id' => $organizationId,
                'user_id' => $userId,
                'template_id' => $template->id,
                'type' => $type ?? NotificationType::from($template->type),
                'channel' => $template->type,
                'priority' => $priority ?? NotificationPriority::NORMAL,
                'status' => NotificationStatus::PENDING,
                'subject' => $rendered['subject'],
                'body' => $rendered['body_html'] ?? $rendered['body_text'],
                'data' => $data,
                'metadata' => [
                    'template_code' => $templateCode,
                    'rendered_at' => now()->toIso8601String(),
                ],
                'retry_count' => 0,
                'max_retries' => config('notification.max_retries', 3),
            ]);

            // Dispatch notification
            $this->dispatcher->dispatch($notification);

            return $notification->fresh();
        });
    }

    /**
     * Send bulk notifications to multiple users
     */
    public function sendBulk(
        array $userIds,
        string $templateCode,
        array $data = [],
        ?NotificationType $type = null,
        ?NotificationPriority $priority = null
    ): array {
        $notifications = [];

        foreach ($userIds as $userId) {
            try {
                $notifications[] = $this->send($userId, $templateCode, $data, $type, $priority);
            } catch (\Exception $e) {
                // Log error but continue with other users
                logger()->error('Failed to send bulk notification', [
                    'user_id' => $userId,
                    'template_code' => $templateCode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notifications;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId): Notification
    {
        $notification = $this->notificationRepository->findById($notificationId);

        if ($notification->isRead()) {
            return $notification;
        }

        return TransactionHelper::execute(function () use ($notification) {
            $notification->markAsRead();

            // Fire event
            event(new NotificationRead($notification));

            return $notification->fresh();
        });
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): int
    {
        return TransactionHelper::execute(function () use ($userId) {
            return $this->notificationRepository->markAllAsReadByUser($userId);
        });
    }

    /**
     * Delete a notification
     */
    public function delete(int $notificationId): bool
    {
        return TransactionHelper::execute(function () use ($notificationId) {
            $notification = $this->notificationRepository->findById($notificationId);

            return $notification->delete();
        });
    }

    /**
     * Schedule a notification for future delivery
     */
    public function schedule(
        int $userId,
        string $templateCode,
        array $data,
        \DateTimeInterface $scheduledAt,
        ?NotificationType $type = null,
        ?NotificationPriority $priority = null
    ): Notification {
        return TransactionHelper::execute(function () use ($userId, $templateCode, $data, $scheduledAt, $type, $priority) {
            // Get template
            $template = $this->templateRepository->findByCode($templateCode);

            // Validate data against template
            $this->templateService->validate($templateCode, $data);

            // Render template
            $rendered = $this->templateService->render($templateCode, $data);

            // Get tenant and organization from auth context
            $tenantId = auth()->user()?->tenant_id ?? null;
            $organizationId = auth()->user()?->organization_id ?? null;

            // Create notification with scheduled_at
            return $this->notificationRepository->create([
                'tenant_id' => $tenantId,
                'organization_id' => $organizationId,
                'user_id' => $userId,
                'template_id' => $template->id,
                'type' => $type ?? NotificationType::from($template->type),
                'channel' => $template->type,
                'priority' => $priority ?? NotificationPriority::NORMAL,
                'status' => NotificationStatus::PENDING,
                'subject' => $rendered['subject'],
                'body' => $rendered['body_html'] ?? $rendered['body_text'],
                'data' => $data,
                'metadata' => [
                    'template_code' => $templateCode,
                    'rendered_at' => now()->toIso8601String(),
                ],
                'scheduled_at' => $scheduledAt,
                'retry_count' => 0,
                'max_retries' => config('notification.max_retries', 3),
            ]);
        });
    }

    /**
     * Get unread notifications count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepository->countUnreadByUser($userId);
    }

    /**
     * Retry failed notification
     */
    public function retry(int $notificationId): Notification
    {
        $notification = $this->notificationRepository->findById($notificationId);

        if (! $notification->hasFailed()) {
            throw new \InvalidArgumentException('Only failed notifications can be retried');
        }

        if ($notification->retry_count >= $notification->max_retries) {
            throw new \InvalidArgumentException('Maximum retry attempts reached');
        }

        return TransactionHelper::execute(function () use ($notification) {
            // Reset status to pending
            $notification->update([
                'status' => NotificationStatus::PENDING,
                'error_message' => null,
            ]);

            // Dispatch notification
            $this->dispatcher->dispatch($notification->fresh());

            return $notification->fresh();
        });
    }
}

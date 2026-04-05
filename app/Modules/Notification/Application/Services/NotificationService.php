<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

use Modules\Notification\Application\Contracts\NotificationServiceInterface;
use Modules\Notification\Domain\Entities\Notification;
use Modules\Notification\Domain\Exceptions\NotificationNotFoundException;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationRepositoryInterface;

class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notifications,
    ) {}

    /** @return Notification[] */
    public function listForUser(int $tenantId, int $userId, bool $unreadOnly = false): array
    {
        return $this->notifications->findByUser($tenantId, $userId, $unreadOnly);
    }

    public function getById(int $id): Notification
    {
        $notification = $this->notifications->findById($id);

        if ($notification === null) {
            throw new NotificationNotFoundException($id);
        }

        return $notification;
    }

    public function markAsRead(int $id): Notification
    {
        $notification = $this->getById($id);

        if (!$notification->isRead()) {
            $notification->markAsRead(new \DateTime());
            $this->notifications->save($notification);
        }

        return $notification;
    }

    public function markAllRead(int $tenantId, int $userId): void
    {
        $this->notifications->markAllReadForUser($tenantId, $userId, new \DateTime());
    }

    public function countUnread(int $tenantId, int $userId): int
    {
        return $this->notifications->countUnreadForUser($tenantId, $userId);
    }

    public function delete(int $id): void
    {
        $this->getById($id); // throws if not found
        $this->notifications->delete($id);
    }
}

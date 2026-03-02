<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

use Modules\Notification\Application\Commands\DeleteNotificationCommand;
use Modules\Notification\Application\Commands\MarkNotificationReadCommand;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Handlers\DeleteNotificationHandler;
use Modules\Notification\Application\Handlers\MarkNotificationReadHandler;
use Modules\Notification\Application\Handlers\SendNotificationHandler;
use Modules\Notification\Domain\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Domain\Entities\Notification;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $repository,
        private readonly SendNotificationHandler $sendHandler,
        private readonly MarkNotificationReadHandler $markReadHandler,
        private readonly DeleteNotificationHandler $deleteHandler,
    ) {}

    public function sendNotification(SendNotificationCommand $cmd): Notification
    {
        return $this->sendHandler->handle($cmd);
    }

    public function markRead(MarkNotificationReadCommand $cmd): ?Notification
    {
        return $this->markReadHandler->handle($cmd);
    }

    public function deleteNotification(DeleteNotificationCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findById(int $id, int $tenantId): ?Notification
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findAll(int $tenantId, int $userId, int $page, int $perPage): array
    {
        return $this->repository->findAll($tenantId, $userId, $page, $perPage);
    }

    public function findUnread(int $tenantId, int $userId): array
    {
        return $this->repository->findUnread($tenantId, $userId);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

use Modules\Notification\Application\Commands\CreateNotificationTemplateCommand;
use Modules\Notification\Application\Commands\DeleteNotificationTemplateCommand;
use Modules\Notification\Application\Commands\UpdateNotificationTemplateCommand;
use Modules\Notification\Application\Handlers\CreateNotificationTemplateHandler;
use Modules\Notification\Application\Handlers\DeleteNotificationTemplateHandler;
use Modules\Notification\Application\Handlers\UpdateNotificationTemplateHandler;
use Modules\Notification\Domain\Contracts\NotificationTemplateRepositoryInterface;
use Modules\Notification\Domain\Entities\NotificationTemplate;

class NotificationTemplateService
{
    public function __construct(
        private readonly NotificationTemplateRepositoryInterface $repository,
        private readonly CreateNotificationTemplateHandler $createHandler,
        private readonly UpdateNotificationTemplateHandler $updateHandler,
        private readonly DeleteNotificationTemplateHandler $deleteHandler,
    ) {}

    public function createTemplate(CreateNotificationTemplateCommand $cmd): NotificationTemplate
    {
        return $this->createHandler->handle($cmd);
    }

    public function updateTemplate(UpdateNotificationTemplateCommand $cmd): NotificationTemplate
    {
        return $this->updateHandler->handle($cmd);
    }

    public function deleteTemplate(DeleteNotificationTemplateCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findById(int $id, int $tenantId): ?NotificationTemplate
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findAll(int $tenantId, int $page, int $perPage): array
    {
        return $this->repository->findAll($tenantId, $page, $perPage);
    }

    public function findByChannel(int $tenantId, string $channel): array
    {
        return $this->repository->findByChannel($tenantId, $channel);
    }
}

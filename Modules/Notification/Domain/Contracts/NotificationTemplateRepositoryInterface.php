<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Contracts;

use Modules\Notification\Domain\Entities\NotificationTemplate;

interface NotificationTemplateRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?NotificationTemplate;

    public function findAll(int $tenantId, int $page, int $perPage): array;

    public function findByChannel(int $tenantId, string $channel): array;

    public function findByEventType(int $tenantId, string $eventType): ?NotificationTemplate;

    public function save(NotificationTemplate $template): NotificationTemplate;

    public function delete(int $id, int $tenantId): void;
}

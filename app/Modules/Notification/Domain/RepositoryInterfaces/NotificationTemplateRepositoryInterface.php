<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\RepositoryInterfaces;

use Modules\Notification\Domain\Entities\NotificationTemplate;

interface NotificationTemplateRepositoryInterface
{
    public function findById(int $id): ?NotificationTemplate;

    public function findByTypeAndChannel(int $tenantId, string $type, string $channel): ?NotificationTemplate;

    /** @return NotificationTemplate[] */
    public function findByTenant(int $tenantId): array;

    public function save(NotificationTemplate $template): NotificationTemplate;

    public function delete(int $id): void;
}

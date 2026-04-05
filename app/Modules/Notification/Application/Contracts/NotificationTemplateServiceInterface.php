<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Contracts;

use Modules\Notification\Domain\Entities\NotificationTemplate;

interface NotificationTemplateServiceInterface
{
    /** @return NotificationTemplate[] */
    public function listByTenant(int $tenantId): array;

    public function getById(int $id): NotificationTemplate;

    public function create(array $data): NotificationTemplate;

    public function update(int $id, array $data): NotificationTemplate;

    public function activate(int $id): NotificationTemplate;

    public function deactivate(int $id): NotificationTemplate;

    public function delete(int $id): void;
}

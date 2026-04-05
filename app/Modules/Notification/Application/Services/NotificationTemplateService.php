<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

use Modules\Notification\Application\Contracts\NotificationTemplateServiceInterface;
use Modules\Notification\Domain\Entities\NotificationTemplate;
use Modules\Notification\Domain\Exceptions\NotificationTemplateNotFoundException;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationTemplateRepositoryInterface;

class NotificationTemplateService implements NotificationTemplateServiceInterface
{
    public function __construct(
        private readonly NotificationTemplateRepositoryInterface $templates,
    ) {}

    /** @return NotificationTemplate[] */
    public function listByTenant(int $tenantId): array
    {
        return $this->templates->findByTenant($tenantId);
    }

    public function getById(int $id): NotificationTemplate
    {
        $template = $this->templates->findById($id);

        if ($template === null) {
            throw new NotificationTemplateNotFoundException($id);
        }

        return $template;
    }

    public function create(array $data): NotificationTemplate
    {
        $template = new NotificationTemplate(
            null,
            $data['tenant_id'] ?? null,
            $data['type'],
            $data['name'],
            $data['channel'],
            $data['subject'],
            $data['body'],
            $data['variables'] ?? [],
            $data['is_active']  ?? true,
            new \DateTime(),
            new \DateTime(),
        );

        return $this->templates->save($template);
    }

    public function update(int $id, array $data): NotificationTemplate
    {
        $existing = $this->getById($id);

        $updated = new NotificationTemplate(
            $existing->getId(),
            $existing->getTenantId(),
            $data['type']      ?? $existing->getType(),
            $data['name']      ?? $existing->getName(),
            $data['channel']   ?? $existing->getChannel(),
            $data['subject']   ?? $existing->getSubject(),
            $data['body']      ?? $existing->getBody(),
            $data['variables'] ?? $existing->getVariables(),
            $data['is_active'] ?? $existing->isActive(),
            $existing->getCreatedAt(),
            new \DateTime(),
        );

        return $this->templates->save($updated);
    }

    public function activate(int $id): NotificationTemplate
    {
        $template = $this->getById($id);
        $template->activate();
        return $this->templates->save($template);
    }

    public function deactivate(int $id): NotificationTemplate
    {
        $template = $this->getById($id);
        $template->deactivate();
        return $this->templates->save($template);
    }

    public function delete(int $id): void
    {
        $this->getById($id);
        $this->templates->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Notification\Domain\Entities\NotificationTemplate;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationTemplateRepositoryInterface;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationTemplateModel;

class EloquentNotificationTemplateRepository implements NotificationTemplateRepositoryInterface
{
    public function __construct(private readonly NotificationTemplateModel $model) {}

    private function toEntity(NotificationTemplateModel $m): NotificationTemplate
    {
        return new NotificationTemplate(
            $m->id,
            $m->tenant_id,
            $m->type,
            $m->name,
            $m->channel,
            $m->subject,
            $m->body,
            $m->variables ?? [],
            (bool) $m->is_active,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findById(int $id): ?NotificationTemplate
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTypeAndChannel(int $tenantId, string $type, string $channel): ?NotificationTemplate
    {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->first();

        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('type')
            ->orderBy('channel')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function save(NotificationTemplate $template): NotificationTemplate
    {
        $data = [
            'tenant_id'  => $template->getTenantId(),
            'type'       => $template->getType(),
            'name'       => $template->getName(),
            'channel'    => $template->getChannel(),
            'subject'    => $template->getSubject(),
            'body'       => $template->getBody(),
            'variables'  => $template->getVariables(),
            'is_active'  => $template->isActive(),
        ];

        if ($template->getId() === null) {
            $m = $this->model->newQuery()->create($data);
        } else {
            $m = $this->model->newQuery()->findOrFail($template->getId());
            $m->update($data);
            $m = $m->fresh();
        }

        return $this->toEntity($m);
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->where('id', $id)->delete();
    }
}

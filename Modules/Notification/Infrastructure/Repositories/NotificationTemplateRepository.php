<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Notification\Domain\Contracts\NotificationTemplateRepositoryInterface;
use Modules\Notification\Domain\Entities\NotificationTemplate;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Infrastructure\Models\NotificationTemplateModel;

class NotificationTemplateRepository extends BaseRepository implements NotificationTemplateRepositoryInterface
{
    protected function model(): string
    {
        return NotificationTemplateModel::class;
    }

    public function findById(int $id, int $tenantId): ?NotificationTemplate
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page, int $perPage): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (NotificationTemplateModel $m) => $this->toDomain($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findByChannel(int $tenantId, string $channel): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('channel', $channel)
            ->get()
            ->map(fn (NotificationTemplateModel $m) => $this->toDomain($m))
            ->all();
    }

    public function findByEventType(int $tenantId, string $eventType): ?NotificationTemplate
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(NotificationTemplate $template): NotificationTemplate
    {
        if ($template->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $template->tenantId)
                ->findOrFail($template->id);
        } else {
            $model = new NotificationTemplateModel;
            $model->tenant_id = $template->tenantId;
        }

        $model->channel = $template->channel->value;
        $model->event_type = $template->eventType;
        $model->name = $template->name;
        $model->subject = $template->subject;
        $model->body = $template->body;
        $model->is_active = $template->isActive;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    private function toDomain(NotificationTemplateModel $model): NotificationTemplate
    {
        return new NotificationTemplate(
            id: $model->id,
            tenantId: $model->tenant_id,
            channel: NotificationChannel::from($model->channel),
            eventType: $model->event_type,
            name: $model->name,
            subject: $model->subject,
            body: $model->body,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Crm\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Crm\Domain\Contracts\ActivityRepositoryInterface;
use Modules\Crm\Domain\Entities\Activity;
use Modules\Crm\Domain\Enums\ActivityType;
use Modules\Crm\Infrastructure\Models\ActivityModel;

class ActivityRepository extends BaseRepository implements ActivityRepositoryInterface
{
    protected function model(): string
    {
        return ActivityModel::class;
    }

    public function findById(int $id, int $tenantId): ?Activity
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (ActivityModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findByContact(int $contactId, int $tenantId): array
    {
        return $this->newQuery()
            ->where('contact_id', $contactId)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (ActivityModel $m) => $this->toDomain($m))
            ->all();
    }

    public function findByLead(int $leadId, int $tenantId): array
    {
        return $this->newQuery()
            ->where('lead_id', $leadId)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (ActivityModel $m) => $this->toDomain($m))
            ->all();
    }

    public function save(Activity $activity): Activity
    {
        if ($activity->id !== null) {
            $model = $this->newQuery()
                ->where('id', $activity->id)
                ->where('tenant_id', $activity->tenantId)
                ->firstOrFail();
        } else {
            $model = new ActivityModel;
            $model->tenant_id = $activity->tenantId;
        }

        $model->contact_id = $activity->contactId;
        $model->lead_id = $activity->leadId;
        $model->type = $activity->type->value;
        $model->subject = $activity->subject;
        $model->description = $activity->description;
        $model->scheduled_at = $activity->scheduledAt;
        $model->completed_at = $activity->completedAt;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($model === null) {
            throw new \DomainException("Activity with ID {$id} not found.");
        }

        $model->delete();
    }

    private function toDomain(ActivityModel $model): Activity
    {
        return new Activity(
            id: $model->id,
            tenantId: $model->tenant_id,
            contactId: $model->contact_id,
            leadId: $model->lead_id,
            type: ActivityType::from($model->type),
            subject: $model->subject,
            description: $model->description,
            scheduledAt: $model->scheduled_at?->toIso8601String(),
            completedAt: $model->completed_at?->toIso8601String(),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}

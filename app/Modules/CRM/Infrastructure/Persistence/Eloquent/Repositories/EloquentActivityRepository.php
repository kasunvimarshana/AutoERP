<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\CRM\Domain\Entities\Activity;
use Modules\CRM\Domain\RepositoryInterfaces\ActivityRepositoryInterface;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\ActivityModel;

class EloquentActivityRepository implements ActivityRepositoryInterface
{
    public function __construct(private readonly ActivityModel $model) {}

    public function findById(int $id): ?Activity
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        $q = $this->model->newQuery()->where('tenant_id', $tenantId);
        if (!empty($filters['type'])) $q->where('type', $filters['type']);
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['owner_id'])) $q->where('owner_id', $filters['owner_id']);
        return $q->orderByDesc('scheduled_at')->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): Activity
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?Activity
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }

    private function toEntity(ActivityModel $m): Activity
    {
        return new Activity(
            $m->id, $m->tenant_id, $m->type, $m->subject, $m->description,
            $m->status, $m->owner_id, $m->contact_id, $m->lead_id, $m->opportunity_id,
            $m->scheduled_at, $m->completed_at, $m->created_at, $m->updated_at,
        );
    }
}

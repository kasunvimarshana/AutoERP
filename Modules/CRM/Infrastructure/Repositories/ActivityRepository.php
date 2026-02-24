<?php
namespace Modules\CRM\Infrastructure\Repositories;
use Modules\CRM\Domain\Contracts\ActivityRepositoryInterface;
use Modules\CRM\Infrastructure\Models\ActivityModel;
class ActivityRepository implements ActivityRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ActivityModel::find($id);
    }
    public function findByRelated(string $relatedType, string $relatedId): object
    {
        return ActivityModel::where('related_type', $relatedType)
            ->where('related_id', $relatedId)
            ->latest()
            ->get();
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = ActivityModel::query();
        if (!empty($filters['type'])) $query->where('type', $filters['type']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['assigned_to'])) $query->where('assigned_to', $filters['assigned_to']);
        if (!empty($filters['related_type'])) $query->where('related_type', $filters['related_type']);
        if (!empty($filters['related_id'])) $query->where('related_id', $filters['related_id']);
        return $query->latest()->paginate($perPage);
    }
    public function create(array $data): object
    {
        return ActivityModel::create($data);
    }
    public function update(string $id, array $data): object
    {
        $activity = ActivityModel::findOrFail($id);
        $activity->update($data);
        return $activity->fresh();
    }
    public function delete(string $id): bool
    {
        return ActivityModel::findOrFail($id)->delete();
    }
}

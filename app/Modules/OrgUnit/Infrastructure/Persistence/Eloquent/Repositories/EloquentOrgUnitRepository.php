<?php
declare(strict_types=1);
namespace Modules\OrgUnit\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\OrgUnit\Domain\Entities\OrgUnit;
use Modules\OrgUnit\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;
use Modules\OrgUnit\Infrastructure\Persistence\Eloquent\Models\OrgUnitModel;

class EloquentOrgUnitRepository implements OrgUnitRepositoryInterface
{
    public function __construct(private readonly OrgUnitModel $model) {}

    public function findById(int $id): ?OrgUnit
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findRoots(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function findChildren(int $tenantId, int $parentId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('parent_id', $parentId)
            ->orderBy('name')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function findDescendants(int $tenantId, string $path): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('path', 'like', $path . '%')
            ->orderBy('path')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function findAncestors(int $tenantId, string $path): array
    {
        // Extract ancestor IDs from materialized path "/1/5/12/" -> [1, 5, 12]
        $ids = array_filter(explode('/', $path), fn($s) => $s !== '');
        if (empty($ids)) return [];

        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->orderBy('level')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function findAllByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('path')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): OrgUnit
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?OrgUnit
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

    public function updateDescendantPaths(
        string $oldPathPrefix,
        string $newPathPrefix,
        int    $levelDelta
    ): void {
        $descendants = $this->model->newQuery()
            ->where('path', 'like', $oldPathPrefix . '%')
            ->get();

        foreach ($descendants as $d) {
            $d->update([
                'path'  => $newPathPrefix . substr((string) $d->path, strlen($oldPathPrefix)),
                'level' => $d->level + $levelDelta,
            ]);
        }
    }

    public function existsByCode(int $tenantId, string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    private function toEntity(OrgUnitModel $m): OrgUnit
    {
        return new OrgUnit(
            $m->id,
            $m->tenant_id,
            $m->parent_id,
            $m->type,
            $m->code,
            $m->name,
            $m->description,
            $m->manager_id,
            $m->level,
            $m->path,
            (bool) $m->is_active,
            $m->created_at,
            $m->updated_at,
        );
    }
}

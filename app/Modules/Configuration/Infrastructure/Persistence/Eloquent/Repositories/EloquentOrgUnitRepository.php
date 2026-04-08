<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Configuration\Domain\Entities\OrgUnit;
use Modules\Configuration\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\OrgUnitModel;

class EloquentOrgUnitRepository implements OrgUnitRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?OrgUnit
    {
        $model = OrgUnitModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return OrgUnitModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('level')
            ->orderBy('name')
            ->get()
            ->map(fn(OrgUnitModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findChildren(string $tenantId, string $parentId): array
    {
        return OrgUnitModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('parent_id', $parentId)
            ->get()
            ->map(fn(OrgUnitModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findDescendants(string $tenantId, string $path): array
    {
        return OrgUnitModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('path', 'like', $path . '/%')
            ->orderBy('level')
            ->get()
            ->map(fn(OrgUnitModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(OrgUnit $orgUnit): void
    {
        /** @var OrgUnitModel $model */
        $model = OrgUnitModel::withoutGlobalScopes()->findOrNew($orgUnit->id);

        $model->fill([
            'tenant_id' => $orgUnit->tenantId,
            'name'      => $orgUnit->name,
            'type'      => $orgUnit->type,
            'code'      => $orgUnit->code,
            'parent_id' => $orgUnit->parentId,
            'path'      => $orgUnit->path,
            'level'     => $orgUnit->level,
            'is_active' => $orgUnit->isActive,
            'metadata'  => $orgUnit->metadata,
        ]);

        if (! $model->exists) {
            $model->id = $orgUnit->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        OrgUnitModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }

    private function mapToEntity(OrgUnitModel $model): OrgUnit
    {
        return new OrgUnit(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            name: (string) $model->name,
            type: (string) $model->type,
            code: (string) $model->code,
            parentId: $model->parent_id !== null ? (string) $model->parent_id : null,
            path: (string) $model->path,
            level: (int) $model->level,
            isActive: (bool) $model->is_active,
            metadata: (array) ($model->metadata ?? []),
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}

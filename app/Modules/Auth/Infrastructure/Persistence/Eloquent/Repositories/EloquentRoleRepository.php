<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Domain\Entities\Role;
use Modules\Auth\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\RoleModel;

class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Role
    {
        $model = RoleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByName(string $tenantId, string $name): ?Role
    {
        $model = RoleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return RoleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(RoleModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(Role $role): void
    {
        /** @var RoleModel $model */
        $model = RoleModel::withoutGlobalScopes()->findOrNew($role->id);

        $model->fill([
            'tenant_id'   => $role->tenantId,
            'name'        => $role->name,
            'guard'       => $role->guard,
            'permissions' => $role->permissions,
        ]);

        if (! $model->exists) {
            $model->id = $role->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        RoleModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }

    private function mapToEntity(RoleModel $model): Role
    {
        return new Role(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            name: (string) $model->name,
            guard: (string) $model->guard,
            permissions: (array) ($model->permissions ?? []),
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}

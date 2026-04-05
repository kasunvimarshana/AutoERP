<?php
declare(strict_types=1);
namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Domain\Entities\Permission;
use Modules\Authorization\Domain\Entities\Role;
use Modules\Authorization\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleModel;

class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function __construct(private readonly RoleModel $model) {}

    private function toEntity(RoleModel $m): Role
    {
        return new Role($m->id, $m->tenant_id, $m->name, $m->slug, $m->description, $m->created_at, $m->updated_at);
    }

    private function toPermissionEntity(PermissionModel $m): Permission
    {
        return new Permission($m->id, $m->name, $m->slug, $m->module, $m->description, $m->created_at, $m->updated_at);
    }

    public function findById(int $id): ?Role
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function create(array $data): Role
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?Role
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) {
            return null;
        }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool) $m->delete() : false;
    }

    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $m = $this->model->newQuery()->findOrFail($roleId);
        $m->permissions()->sync($permissionIds);
    }

    public function getPermissions(int $roleId): array
    {
        $m = $this->model->newQuery()->with('permissions')->findOrFail($roleId);
        return $m->permissions->map(fn($p) => $this->toPermissionEntity($p))->all();
    }
}

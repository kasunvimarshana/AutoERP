<?php
namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Authorization\Domain\Entities\Role;
use Modules\Authorization\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentRoleRepository extends EloquentRepository implements RoleRepositoryInterface
{
    public function __construct(RoleModel $model) { parent::__construct($model); }

    public function findById(int $id): ?Role
    {
        $m = parent::findById($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByName(int $tenantId, string $name): ?Role
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('name', $name)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAll(int $tenantId, int $perPage = 50): LengthAwarePaginator
    {
        return $this->model->where('tenant_id', $tenantId)->paginate($perPage);
    }

    public function create(array $data): Role
    {
        return $this->toEntity(parent::create($data));
    }

    public function delete(Role $role): bool
    {
        $m = $this->model->findOrFail($role->id);
        return parent::delete($m);
    }

    public function syncPermissions(Role $role, array $permissionIds): void
    {
        $m = $this->model->findOrFail($role->id);
        $m->permissions()->sync($permissionIds);
    }

    public function getPermissionIds(Role $role): array
    {
        $m = $this->model->with('permissions')->findOrFail($role->id);
        return $m->permissions->pluck('id')->toArray();
    }

    private function toEntity(object $m): Role
    {
        return new Role(
            id:          $m->id,
            tenantId:    $m->tenant_id,
            name:        $m->name,
            guardName:   $m->guard_name,
            description: $m->description ?? null,
        );
    }
}

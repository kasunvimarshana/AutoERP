<?php
namespace Modules\User\Infrastructure\Repositories;
use Modules\User\Domain\Contracts\RoleRepositoryInterface;
use Modules\User\Infrastructure\Models\RoleModel;
class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(private RoleModel $model) {}
    public function findById(string $id): ?object
    {
        return $this->model->newQuery()->find($id);
    }
    public function findByName(string $name, string $tenantId): ?object
    {
        return $this->model->newQuery()->where('name', $name)->where('tenant_id', $tenantId)->first();
    }
    public function create(array $data): object
    {
        return $this->model->newQuery()->create($data);
    }
    public function update(string $id, array $data): object
    {
        $role = $this->model->newQuery()->findOrFail($id);
        $role->update($data);
        return $role->fresh();
    }
    public function delete(string $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }
    public function assignPermission(string $roleId, string $permissionId): void
    {
        $role = $this->model->newQuery()->findOrFail($roleId);
        $role->permissions()->syncWithoutDetaching([$permissionId]);
    }
    public function paginate(array $filters, int $perPage): object
    {
        $query = $this->model->newQuery();
        foreach ($filters as $key => $value) {
            if ($value !== null) {
                $query->where($key, $value);
            }
        }
        return $query->paginate($perPage);
    }
}

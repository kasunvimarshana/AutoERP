<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\RoleModel;

class EloquentRoleRepository extends EloquentRepository implements RoleRepositoryInterface
{
    public function __construct(RoleModel $model)
    {
        parent::__construct($model);
    }

    public function findByName(int $tenantId, string $name): ?Role
    {
        $model = $this->model->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(Role $role): Role
    {
        if ($role->getId()) {
            $model = $this->update($role->getId(), [
                'tenant_id' => $role->getTenantId(),
                'name' => $role->getName(),
            ]);
        } else {
            $model = $this->create([
                'tenant_id' => $role->getTenantId(),
                'name' => $role->getName(),
            ]);
        }

        return $this->toDomainEntity($model);
    }

    public function syncPermissions(Role $role, array $permissionIds): void
    {
        $model = $this->model->find($role->getId());
        if ($model) {
            $model->permissions()->sync($permissionIds);
        }
    }

    private function toDomainEntity(RoleModel $model): Role
    {
        $role = new Role($model->tenant_id, $model->name, $model->id);
        if ($model->relationLoaded('permissions')) {
            foreach ($model->permissions as $permModel) {
                $role->grantPermission(new Permission($permModel->tenant_id, $permModel->name, $permModel->id));
            }
        }

        return $role;
    }
}

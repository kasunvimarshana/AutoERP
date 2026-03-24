<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    /**
     * Find a role by ID and convert to domain entity.
     *
     * {@inheritdoc}
     */
    public function find($id, array $columns = ['*']): ?Role
    {
        /** @var RoleModel|null $model */
        $model = $this->model->with('permissions')->find($id);

        return $model ? $this->toDomainEntity($model) : null;
    }

    /**
     * Paginate roles and convert each row to a domain entity.
     *
     * {@inheritdoc}
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        $this->with(['permissions']);
        $paginator = parent::paginate($perPage, $columns, $pageName, $page);

        return $paginator->through(fn (RoleModel $model) => $this->toDomainEntity($model));
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

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
        $this->setDomainEntityMapper(fn (RoleModel $model): Role => $this->mapModelToDomainEntity($model));
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
                'guard_name' => $role->getGuardName(),
                'description' => $role->getDescription(),
            ]);
        } else {
            $model = $this->create([
                'tenant_id' => $role->getTenantId(),
                'name' => $role->getName(),
                'guard_name' => $role->getGuardName(),
                'description' => $role->getDescription(),
            ]);
        }

        /** @var RoleModel $model */

        return $this->mapModelToDomainEntity($model);
    }

    public function syncPermissions(Role $role, array $permissionIds): void
    {
        /** @var RoleModel|null $model */
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
    public function find(int|string $id, array $columns = ['*']): ?Role
    {
        $this->with(['permissions']);

        return parent::find($id, $columns);
    }

    /**
     * Paginate roles and convert each row to a domain entity.
     *
     * {@inheritdoc}
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        $this->with(['permissions']);

        return parent::paginate($perPage, $columns, $pageName, $page);
    }

    private function mapModelToDomainEntity(RoleModel $model): Role
    {
        $role = new Role(
            tenantId: (int) $model->tenant_id,
            name: (string) $model->name,
            guardName: (string) $model->guard_name,
            description: $model->description,
            id: (int) $model->id,
        );

        if ($model->relationLoaded('permissions')) {
            foreach ($model->permissions as $permModel) {
                $role->grantPermission(new Permission(
                    tenantId: (int) $permModel->tenant_id,
                    name: (string) $permModel->name,
                    guardName: (string) $permModel->guard_name,
                    module: (string) ($permModel->module ?? 'general'),
                    description: $permModel->description,
                    id: (int) $permModel->id,
                ));
            }
        }

        return $role;
    }
}

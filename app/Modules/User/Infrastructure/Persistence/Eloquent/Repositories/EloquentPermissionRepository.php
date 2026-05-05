<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\PermissionModel;

class EloquentPermissionRepository extends EloquentRepository implements PermissionRepositoryInterface
{
    public function __construct(PermissionModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PermissionModel $model): Permission => $this->mapModelToDomainEntity($model));
    }

    public function findByName(int $tenantId, string $name): ?Permission
    {
        $model = $this->model->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(Permission $permission): Permission
    {
        if ($permission->getId()) {
            $model = $this->update($permission->getId(), [
                'tenant_id' => $permission->getTenantId(),
                'name' => $permission->getName(),
                'guard_name' => $permission->getGuardName(),
                'module' => $permission->getModule(),
                'description' => $permission->getDescription(),
            ]);
        } else {
            $model = $this->create([
                'tenant_id' => $permission->getTenantId(),
                'name' => $permission->getName(),
                'guard_name' => $permission->getGuardName(),
                'module' => $permission->getModule(),
                'description' => $permission->getDescription(),
            ]);
        }

        /** @var PermissionModel $model */

        return $this->mapModelToDomainEntity($model);
    }

    private function mapModelToDomainEntity(PermissionModel $model): Permission
    {
        return new Permission($model->tenant_id, $model->name, $model->id);
    }

    /**
     * Find a permission by ID and convert to domain entity.
     *
     * {@inheritdoc}
     */
    public function find(int|string $id, array $columns = ['*']): ?Permission
    {
        return parent::find($id, $columns);
    }

    /**
     * Paginate permissions and convert each row to a domain entity.
     *
     * {@inheritdoc}
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        return parent::paginate($perPage, $columns, $pageName, $page);
    }
}

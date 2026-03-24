<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\PermissionModel;

class EloquentPermissionRepository extends EloquentRepository implements PermissionRepositoryInterface
{
    public function __construct(PermissionModel $model)
    {
        parent::__construct($model);
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
            ]);
        } else {
            $model = $this->create([
                'tenant_id' => $permission->getTenantId(),
                'name' => $permission->getName(),
            ]);
        }

        return $this->toDomainEntity($model);
    }

    private function toDomainEntity(PermissionModel $model): Permission
    {
        return new Permission($model->tenant_id, $model->name, $model->id);
    }
}

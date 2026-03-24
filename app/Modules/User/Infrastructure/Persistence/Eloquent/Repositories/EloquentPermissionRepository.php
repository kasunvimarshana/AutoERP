<?php

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\User\Domain\Entities\Permission;
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

    private function toDomainEntity(PermissionModel $model): Permission
    {
        return new Permission($model->tenant_id, $model->name, $model->id);
    }
}

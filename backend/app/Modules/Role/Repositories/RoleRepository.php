<?php

namespace App\Modules\Role\Repositories;

use App\Base\BaseRepository;
use App\Modules\Role\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name): ?Model
    {
        return $this->model->where('name', $name)->first();
    }

    public function findByTenant(string $tenantId, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model
            ->byTenant($tenantId)
            ->with($relations)
            ->get($columns);
    }

    public function getWithPermissions(array $columns = ['*']): Collection
    {
        return $this->model
            ->with('permissions')
            ->get($columns);
    }
}

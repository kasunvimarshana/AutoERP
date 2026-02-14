<?php

namespace App\Modules\Permission\Repositories;

use App\Base\BaseRepository;
use App\Modules\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class PermissionRepository extends BaseRepository
{
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name): ?Model
    {
        return $this->model->where('name', $name)->first();
    }

    public function getByModule(string $module, array $columns = ['*']): Collection
    {
        return $this->model
            ->byModule($module)
            ->get($columns);
    }

    public function getAllGrouped(): Collection
    {
        return $this->model
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');
    }
}

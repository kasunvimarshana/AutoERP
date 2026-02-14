<?php

namespace App\Modules\Permission\Services;

use App\Base\BaseService;
use App\Modules\Permission\Repositories\PermissionRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class PermissionService extends BaseService
{
    public function __construct(PermissionRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getAllPermissions(): Collection
    {
        return $this->repository->all(['*']);
    }

    public function getGrouped(): Collection
    {
        return $this->repository->getAllGrouped();
    }

    public function getPermissionById(string $id): ?Model
    {
        return $this->repository->find($id);
    }

    public function findByName(string $name): ?Model
    {
        return $this->repository->findByName($name);
    }

    public function getByModule(string $module): Collection
    {
        return $this->repository->getByModule($module);
    }
}

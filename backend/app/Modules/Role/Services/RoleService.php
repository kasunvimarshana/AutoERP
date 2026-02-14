<?php

namespace App\Modules\Role\Services;

use App\Base\BaseService;
use App\Modules\Role\Repositories\RoleRepository;
use App\Modules\Role\DTOs\RoleDTO;
use App\Modules\Role\Events\RoleCreated;
use App\Modules\Role\Events\RoleUpdated;
use App\Modules\Role\Events\RoleDeleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class RoleService extends BaseService
{
    public function __construct(RoleRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createRole(RoleDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($dto) {
            $data = $dto->toArray();
            
            $role = $this->repository->create($data);
            
            if (!empty($dto->permissions)) {
                $role->syncPermissions($dto->permissions);
            }

            Event::dispatch(new RoleCreated($role));
            
            return $role->load('permissions');
        });
    }

    public function updateRole(string $id, RoleDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($id, $dto) {
            $role = $this->repository->find($id);
            
            if (!$role) {
                return false;
            }
            
            $data = $dto->toArray();
            
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                if (!empty($dto->permissions)) {
                    $role->syncPermissions($dto->permissions);
                }
                
                $role->refresh();
                Event::dispatch(new RoleUpdated($role));
            }
            
            return $result;
        });
    }

    public function deleteRole(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $role = $this->repository->find($id);
            
            if (!$role) {
                return false;
            }
            
            $result = $this->repository->delete($id);
            
            if ($result) {
                Event::dispatch(new RoleDeleted($role));
            }
            
            return $result;
        });
    }

    public function syncPermissions(string $roleId, array $permissionIds): bool
    {
        return $this->executeInTransaction(function () use ($roleId, $permissionIds) {
            $role = $this->repository->find($roleId);
            
            if (!$role) {
                return false;
            }
            
            $role->syncPermissions($permissionIds);
            
            return true;
        });
    }

    public function assignPermission(string $roleId, string $permissionId): bool
    {
        return $this->executeInTransaction(function () use ($roleId, $permissionId) {
            $role = $this->repository->find($roleId);
            
            if (!$role) {
                return false;
            }
            
            $role->givePermissionTo($permissionId);
            
            return true;
        });
    }

    public function removePermission(string $roleId, string $permissionId): bool
    {
        return $this->executeInTransaction(function () use ($roleId, $permissionId) {
            $role = $this->repository->find($roleId);
            
            if (!$role) {
                return false;
            }
            
            $role->revokePermissionTo($permissionId);
            
            return true;
        });
    }

    public function findByName(string $name): ?Model
    {
        return $this->repository->findByName($name);
    }

    public function findByTenant(string $tenantId)
    {
        return $this->repository->findByTenant($tenantId, ['*'], ['permissions']);
    }

    public function getWithPermissions()
    {
        return $this->repository->getWithPermissions();
    }
}

<?php

namespace Modules\IAM\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;
use Modules\IAM\DTOs\PermissionDTO;
use Modules\IAM\Models\Permission;
use Modules\IAM\Repositories\PermissionRepository;

class PermissionService extends BaseService
{
    public function __construct(
        TenantContext $tenantContext,
        protected PermissionRepository $permissionRepository
    ) {
        parent::__construct($tenantContext);
    }

    public function create(PermissionDTO $dto): Permission
    {
        $this->validateTenant();

        $name = Permission::generateName($dto->resource, $dto->action);

        if ($this->permissionRepository->findByName($name, $this->getTenantId())) {
            throw ValidationException::withMessages([
                'name' => ['A permission with this resource and action already exists.'],
            ]);
        }

        return $this->permissionRepository->create([
            'name' => $name,
            'guard_name' => 'web',
            'description' => $dto->description,
            'resource' => $dto->resource,
            'action' => $dto->action,
            'tenant_id' => $dto->tenant_id ?? $this->getTenantId(),
            'is_system' => $dto->is_system,
        ]);
    }

    public function update(int $id, PermissionDTO $dto): Permission
    {
        $this->validateTenant();

        $permission = $this->permissionRepository->findOrFail($id);

        if ($permission->is_system) {
            throw new \RuntimeException('Cannot modify system permissions');
        }

        if ($permission->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('Permission does not belong to current tenant');
        }

        $name = Permission::generateName($dto->resource, $dto->action);

        if ($name !== $permission->name && $this->permissionRepository->findByName($name, $this->getTenantId())) {
            throw ValidationException::withMessages([
                'name' => ['A permission with this resource and action already exists.'],
            ]);
        }

        $this->permissionRepository->update($permission, [
            'name' => $name,
            'description' => $dto->description,
            'resource' => $dto->resource,
            'action' => $dto->action,
        ]);

        $permission->refresh();

        return $permission;
    }

    public function delete(int $id): void
    {
        $this->validateTenant();

        $permission = $this->permissionRepository->findOrFail($id);

        if ($permission->is_system) {
            throw new \RuntimeException('Cannot delete system permissions');
        }

        if ($permission->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('Permission does not belong to current tenant');
        }

        $this->permissionRepository->delete($permission);
    }

    public function find(int $id): ?Permission
    {
        $permission = $this->permissionRepository->find($id);

        if ($permission && $permission->tenant_id !== $this->getTenantId() && ! $permission->is_system) {
            return null;
        }

        return $permission;
    }

    public function findByName(string $name): ?Permission
    {
        return $this->permissionRepository->findByName($name, $this->getTenantId());
    }

    public function getAll(): Collection
    {
        $this->validateTenant();

        return $this->permissionRepository->getAllForTenant($this->getTenantId());
    }

    public function getSystemPermissions(): Collection
    {
        return $this->permissionRepository->getSystemPermissions();
    }

    public function getCustomPermissions(): Collection
    {
        $this->validateTenant();

        return $this->permissionRepository->getCustomPermissions($this->getTenantId());
    }

    public function getByResource(string $resource): Collection
    {
        $this->validateTenant();

        return $this->permissionRepository->getByResource($resource, $this->getTenantId());
    }

    public function getAllGroupedByResource(): array
    {
        $this->validateTenant();

        return $this->permissionRepository->getAllGroupedByResource($this->getTenantId());
    }

    public function createBulk(string $resource, array $actions, ?string $description = null): Collection
    {
        $this->validateTenant();

        return $this->transaction(function () use ($resource, $actions, $description) {
            $permissions = collect();

            foreach ($actions as $action) {
                $name = Permission::generateName($resource, $action);

                if (! $this->permissionRepository->findByName($name, $this->getTenantId())) {
                    $permission = $this->permissionRepository->create([
                        'name' => $name,
                        'guard_name' => 'web',
                        'description' => $description ?? "Permission to {$action} {$resource}",
                        'resource' => $resource,
                        'action' => $action,
                        'tenant_id' => $this->getTenantId(),
                        'is_system' => false,
                    ]);

                    $permissions->push($permission);
                }
            }

            return $permissions;
        });
    }
}

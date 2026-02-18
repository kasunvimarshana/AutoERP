<?php

namespace Modules\IAM\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;
use Modules\IAM\DTOs\RoleDTO;
use Modules\IAM\Models\Role;
use Modules\IAM\Repositories\RoleRepository;

class RoleService extends BaseService
{
    public function __construct(
        TenantContext $tenantContext,
        protected RoleRepository $roleRepository
    ) {
        parent::__construct($tenantContext);
    }

    public function create(RoleDTO $dto): Role
    {
        $this->validateTenant();

        if ($this->roleRepository->findByName($dto->name, $this->getTenantId())) {
            throw ValidationException::withMessages([
                'name' => ['A role with this name already exists.'],
            ]);
        }

        return $this->transaction(function () use ($dto) {
            $role = $this->roleRepository->create([
                'name' => $dto->name,
                'guard_name' => 'web',
                'description' => $dto->description,
                'tenant_id' => $dto->tenant_id ?? $this->getTenantId(),
                'parent_id' => $dto->parent_id,
                'is_system' => $dto->is_system,
            ]);

            if (! empty($dto->permissions)) {
                $role->syncPermissions($dto->permissions);
            }

            return $role->load('permissions');
        });
    }

    public function update(int $id, RoleDTO $dto): Role
    {
        $this->validateTenant();

        $role = $this->roleRepository->findOrFail($id);

        if ($role->is_system) {
            throw new \RuntimeException('Cannot modify system roles');
        }

        if ($role->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('Role does not belong to current tenant');
        }

        if ($dto->name !== $role->name && $this->roleRepository->findByName($dto->name, $this->getTenantId())) {
            throw ValidationException::withMessages([
                'name' => ['A role with this name already exists.'],
            ]);
        }

        return $this->transaction(function () use ($role, $dto) {
            $this->roleRepository->update($role, [
                'name' => $dto->name,
                'description' => $dto->description,
                'parent_id' => $dto->parent_id,
            ]);

            if ($dto->permissions !== null) {
                $role->syncPermissions($dto->permissions);
            }

            $role->refresh();

            return $role->load('permissions', 'parent');
        });
    }

    public function delete(int $id): void
    {
        $this->validateTenant();

        $role = $this->roleRepository->findOrFail($id);

        if ($role->is_system) {
            throw new \RuntimeException('Cannot delete system roles');
        }

        if ($role->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('Role does not belong to current tenant');
        }

        // Check if role is assigned to any users
        if ($role->users()->count() > 0) {
            throw new \RuntimeException('Cannot delete role that is assigned to users');
        }

        $this->roleRepository->delete($role);
    }

    public function find(int $id): ?Role
    {
        $role = $this->roleRepository->find($id);

        if ($role && $role->tenant_id !== $this->getTenantId() && ! $role->is_system) {
            return null;
        }

        return $role?->load('permissions', 'parent');
    }

    public function findByName(string $name): ?Role
    {
        return $this->roleRepository->findByName($name, $this->getTenantId());
    }

    public function getAll(): Collection
    {
        $this->validateTenant();

        return $this->roleRepository->getAllForTenant($this->getTenantId());
    }

    public function getSystemRoles(): Collection
    {
        return $this->roleRepository->getSystemRoles();
    }

    public function getCustomRoles(): Collection
    {
        $this->validateTenant();

        return $this->roleRepository->getCustomRoles($this->getTenantId());
    }

    public function assignPermissions(int $roleId, array $permissionNames): Role
    {
        $this->validateTenant();

        $role = $this->roleRepository->findOrFail($roleId);

        if ($role->is_system) {
            throw new \RuntimeException('Cannot modify permissions of system roles');
        }

        if ($role->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('Role does not belong to current tenant');
        }

        $role->givePermissionTo($permissionNames);
        $role->refresh();

        return $role->load('permissions');
    }

    public function revokePermissions(int $roleId, array $permissionNames): Role
    {
        $this->validateTenant();

        $role = $this->roleRepository->findOrFail($roleId);

        if ($role->is_system) {
            throw new \RuntimeException('Cannot modify permissions of system roles');
        }

        if ($role->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('Role does not belong to current tenant');
        }

        $role->revokePermissionTo($permissionNames);
        $role->refresh();

        return $role->load('permissions');
    }

    public function syncPermissions(int $roleId, array $permissionNames): Role
    {
        $this->validateTenant();

        $role = $this->roleRepository->findOrFail($roleId);

        if ($role->is_system) {
            throw new \RuntimeException('Cannot modify permissions of system roles');
        }

        if ($role->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('Role does not belong to current tenant');
        }

        $role->syncPermissions($permissionNames);
        $role->refresh();

        return $role->load('permissions');
    }

    public function getHierarchy(): Collection
    {
        $this->validateTenant();

        return $this->roleRepository->getHierarchy($this->getTenantId());
    }

    public function getAllPermissions(int $roleId): Collection
    {
        $role = $this->roleRepository->findOrFail($roleId);

        if (! $role->is_system && $role->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('Role does not belong to current tenant');
        }

        return $role->getAllPermissions();
    }
}

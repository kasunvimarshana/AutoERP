<?php

namespace Modules\IAM\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\IAM\Models\Role;

class RoleRepository extends BaseRepository
{
    protected function model(): string
    {
        return Role::class;
    }

    public function findByName(string $name, ?int $tenantId = null): ?Role
    {
        $query = $this->model->where('name', $name);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    public function getAllForTenant(int $tenantId): Collection
    {
        return $this->model
            ->with('permissions')
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function getSystemRoles(): Collection
    {
        return $this->model
            ->where('is_system', true)
            ->get();
    }

    public function getCustomRoles(int $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_system', false)
            ->get();
    }

    public function getRoleWithPermissions(int $id): ?Role
    {
        return $this->model
            ->with(['permissions', 'parent'])
            ->find($id);
    }

    public function syncPermissions(Role $role, array $permissionIds): void
    {
        $role->syncPermissions($permissionIds);
    }

    public function getHierarchy(int $tenantId): Collection
    {
        return $this->model
            ->with('children')
            ->where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->get();
    }
}

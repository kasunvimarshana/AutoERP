<?php

namespace Modules\IAM\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\IAM\Models\Permission;

class PermissionRepository extends BaseRepository
{
    protected function model(): string
    {
        return Permission::class;
    }

    public function findByName(string $name, ?int $tenantId = null): ?Permission
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
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function getSystemPermissions(): Collection
    {
        return $this->model
            ->where('is_system', true)
            ->get();
    }

    public function getCustomPermissions(int $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_system', false)
            ->get();
    }

    public function getByResource(string $resource, ?int $tenantId = null): Collection
    {
        $query = $this->model->where('resource', $resource);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }

    public function findByResourceAndAction(string $resource, string $action, ?int $tenantId = null): ?Permission
    {
        $query = $this->model
            ->where('resource', $resource)
            ->where('action', $action);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    public function getAllGroupedByResource(?int $tenantId = null): array
    {
        $query = $this->model->newQuery();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get()->groupBy('resource')->toArray();
    }
}

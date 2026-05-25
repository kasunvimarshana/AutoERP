<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\User\Application\Repositories\RolePermissionRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\RolePermissionModel;

final class EloquentRolePermissionRepository extends EloquentRepository implements RolePermissionRepositoryInterface
{
    public function __construct(RolePermissionModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenantRolePermission(?int $tenantId, int $roleId, int $permissionId, ?int $excludeId = null): ?DataRecord
    {
        $query = $this->query()
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId);

        $this->applyTenantScope($query, $tenantId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $model = $query->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    private function applyTenantScope(Builder $query, ?int $tenantId): void
    {
        if ($tenantId === null) {
            $query->whereNull('tenant_id');

            return;
        }

        $query->where('tenant_id', $tenantId);
    }
}

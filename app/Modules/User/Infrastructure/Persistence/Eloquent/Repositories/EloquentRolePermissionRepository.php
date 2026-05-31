<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

    public function listPermissionNamesForTenantRoles(?int $tenantId, array $roleIds): array
    {
        $roleIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $roleId): int => (int) $roleId, $roleIds),
            static fn (int $roleId): bool => $roleId > 0,
        )));

        if ($roleIds === []) {
            return [];
        }

        $query = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->whereIn('role_permissions.role_id', $roleIds)
            ->whereNull('permissions.deleted_at')
            ->select('permissions.name');

        if ($tenantId === null) {
            $query->whereNull('role_permissions.tenant_id');
        } else {
            $query->where('role_permissions.tenant_id', $tenantId);
        }

        return $query
            ->orderBy('permissions.name')
            ->pluck('permissions.name')
            ->map(static fn (mixed $name): string => (string) $name)
            ->unique()
            ->values()
            ->all();
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

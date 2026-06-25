<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;
use Modules\User\Models\RolePermissionModel;

final class EloquentRolePermissionRepository extends EloquentRepository implements RolePermissionRepositoryInterface
{
    public function __construct(RolePermissionModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenantRolePermission(int $tenantId, int $roleId, int $permissionId, ?int $excludeId = null): ?DataRecord
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

    public function listPermissionNamesForTenantRoles(int $tenantId, array $roleIds): array
    {
        $roleIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $roleId): int => (int) $roleId, $roleIds),
            static fn (int $roleId): bool => $roleId > 0,
        )));

        if ($roleIds === []) {
            return [];
        }

        $query = DB::table('role_permissions')
            ->join('permissions', function ($join): void {
                $join->on('permissions.id', '=', 'role_permissions.permission_id')
                    ->on('permissions.tenant_id', '=', 'role_permissions.tenant_id');
            })
            ->where('role_permissions.tenant_id', $tenantId)
            ->whereIn('role_permissions.role_id', $roleIds)
            ->whereNull('permissions.deleted_at')
            ->select('permissions.name');


        return $query
            ->orderBy('permissions.name')
            ->pluck('permissions.name')
            ->map(static fn (mixed $name): string => (string) $name)
            ->unique()
            ->values()
            ->all();
    }

    private function applyTenantScope(Builder $query, int $tenantId): void
    {
        $query->where('tenant_id', $tenantId);
    }
}

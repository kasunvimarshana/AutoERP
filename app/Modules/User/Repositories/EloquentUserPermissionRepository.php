<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;
use Modules\User\Models\UserPermissionModel;

final class EloquentUserPermissionRepository extends EloquentRepository implements UserPermissionRepositoryInterface
{
    public function __construct(UserPermissionModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenantUserPermission(int $tenantId, int $userId, int $permissionId, ?int $excludeId = null): ?DataRecord
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('permission_id', $permissionId);

        $this->applyTenantScope($query, $tenantId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $model = $query->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function listPermissionNamesForTenantUser(int $tenantId, int $userId): array
    {
        $query = DB::table('user_permissions')
            ->join('permissions', function ($join): void {
                $join->on('permissions.id', '=', 'user_permissions.permission_id')
                    ->on('permissions.tenant_id', '=', 'user_permissions.tenant_id');
            })
            ->where('user_permissions.tenant_id', $tenantId)
            ->where('user_permissions.user_id', $userId)
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

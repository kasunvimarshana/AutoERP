<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\User\Application\Repositories\UserPermissionRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserPermissionModel;

final class EloquentUserPermissionRepository extends EloquentRepository implements UserPermissionRepositoryInterface
{
    public function __construct(UserPermissionModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenantUserPermission(?int $tenantId, int $userId, int $permissionId, ?int $excludeId = null): ?DataRecord
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

    public function listPermissionNamesForTenantUser(?int $tenantId, int $userId): array
    {
        $query = DB::table('user_permissions')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_permissions.user_id', $userId)
            ->whereNull('permissions.deleted_at')
            ->select('permissions.name');

        if ($tenantId === null) {
            $query->whereNull('user_permissions.tenant_id');
        } else {
            $query->where('user_permissions.tenant_id', $tenantId);
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

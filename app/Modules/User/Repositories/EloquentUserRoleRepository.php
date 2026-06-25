<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;
use Modules\User\Models\UserRoleModel;

final class EloquentUserRoleRepository extends EloquentRepository implements UserRoleRepositoryInterface
{
    public function __construct(UserRoleModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenantUserRole(int $tenantId, int $userId, int $roleId, ?int $excludeId = null): ?DataRecord
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('role_id', $roleId);

        $this->applyTenantScope($query, $tenantId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $model = $query->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function listRoleSummariesForTenantUser(int $tenantId, int $userId): array
    {
        $query = DB::table('user_roles')
            ->join('roles', function ($join): void {
                $join->on('roles.id', '=', 'user_roles.role_id')
                    ->on('roles.tenant_id', '=', 'user_roles.tenant_id');
            })
            ->where('user_roles.tenant_id', $tenantId)
            ->where('user_roles.user_id', $userId)
            ->whereNull('roles.deleted_at')
            ->select(['roles.id', 'roles.name']);


        return $query
            ->orderBy('roles.name')
            ->get()
            ->map(static fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
            ])
            ->values()
            ->all();
    }

    private function applyTenantScope(Builder $query, int $tenantId): void
    {
        $query->where('tenant_id', $tenantId);
    }
}

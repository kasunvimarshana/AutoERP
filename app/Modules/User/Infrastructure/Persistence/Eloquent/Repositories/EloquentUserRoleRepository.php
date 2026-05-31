<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\User\Application\Repositories\UserRoleRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;

final class EloquentUserRoleRepository extends EloquentRepository implements UserRoleRepositoryInterface
{
    public function __construct(UserRoleModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenantUserRole(?int $tenantId, int $userId, int $roleId, ?int $excludeId = null): ?DataRecord
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

    public function listRoleSummariesForTenantUser(?int $tenantId, int $userId): array
    {
        $query = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->whereNull('roles.deleted_at')
            ->select(['roles.id', 'roles.name']);

        if ($tenantId === null) {
            $query->whereNull('user_roles.tenant_id');
        } else {
            $query->where('user_roles.tenant_id', $tenantId);
        }

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

    private function applyTenantScope(Builder $query, ?int $tenantId): void
    {
        if ($tenantId === null) {
            $query->whereNull('tenant_id');

            return;
        }

        $query->where('tenant_id', $tenantId);
    }
}
